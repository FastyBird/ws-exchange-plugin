#!/usr/bin/python3

#     Copyright 2021. FastyBird s.r.o.
#
#     Licensed under the Apache License, Version 2.0 (the "License");
#     you may not use this file except in compliance with the License.
#     You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#     Unless required by applicable law or agreed to in writing, software
#     distributed under the License is distributed on an "AS IS" BASIS,
#     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#     See the License for the specific language governing permissions and
#     limitations under the License.

"""
WS server plugin websockets server
"""

# Library dependencies
import json
import socket
import ssl
import select
from threading import Thread
from typing import Dict, List
from exchange_plugin.consumer import IConsumer
from exchange_plugin.dispatcher import EventDispatcher
from exchange_plugin.events.messages import MessageReceivedEvent
from kink import inject
from modules_metadata.routing import RoutingKey
from modules_metadata.types import ModuleOrigin

# Library libs
from ws_server_plugin.client import WampClient
from ws_server_plugin.events import ClientSubscribedEvent, ClientUnsubscribedEvent
from ws_server_plugin.exceptions import (
    ClientException,
    HandleDataException,
    HandleRequestException,
    HandleResponseException,
)
from ws_server_plugin.logger import Logger
from ws_server_plugin.types import OPCode


@inject
class WebsocketsServer(Thread):
    """
    Main websockets server instance

    @package        FastyBird:WsServerPlugin!
    @module         server

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """
    __stopped: bool = False

    __request_queue_size: int = 5
    __select_interval: float = 0.1

    __using_ssl: bool = False

    __server_socket: socket.socket

    __listeners: List[int or socket.socket] = []
    __connections: Dict[int, WampClient] = {}

    __secured_context: ssl.SSLContext or None

    __iterator_index = 0

    __event_dispatcher: EventDispatcher
    __exchange_consumer: IConsumer or None = None

    __logger: Logger

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        event_dispatcher: EventDispatcher,
        logger: Logger,
        host: str = "",
        port: int = 9000,
        cert_file: str or None = None,
        key_file: str or None = None,
        ssl_version: int = ssl.PROTOCOL_TLSv1,
        select_interval: float = 0.1,
        exchange_consumer: IConsumer or None = None,
    ) -> None:
        Thread.__init__(self)

        if host == "":
            host = None

        fam = socket.AF_INET6 if host is None else 0  # pylint: disable=no-member

        host_info = socket.getaddrinfo(
            host,
            port,
            fam,
            socket.SOCK_STREAM,  # pylint: disable=no-member
            socket.IPPROTO_TCP,  # pylint: disable=no-member
            socket.AI_PASSIVE,  # pylint: disable=no-member
        )

        self.__server_socket = socket.socket(host_info[0][0], host_info[0][1], host_info[0][2])
        self.__server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)  # pylint: disable=no-member
        self.__server_socket.bind(host_info[0][4])
        self.__server_socket.listen(self.__request_queue_size)

        self.__select_interval = select_interval

        self.__listeners = [self.__server_socket]

        self.__using_ssl = bool(cert_file and key_file)

        if self.__using_ssl:
            self.__secured_context = ssl.SSLContext(ssl_version)
            self.__secured_context.load_cert_chain(cert_file, key_file)

        self.__exchange_consumer = exchange_consumer
        self.__event_dispatcher = event_dispatcher

        self.__logger = logger

        # Threading config...
        self.setDaemon(True)
        self.setName("WebSockets server exchange thread")

    # -----------------------------------------------------------------------------

    def run(self) -> None:
        """Process server communication"""
        self.__stopped = False

        while not self.__stopped:
            self.__handle_request()

    # -----------------------------------------------------------------------------

    def close(self) -> None:
        """Close all opened connections & stop server thread"""
        self.__stopped = True

        self.__server_socket.close()

        for conn in self.__connections.values():
            conn.close()

            self.__handle_close(conn)

        self.__logger.info("WS server was closed")

    # -----------------------------------------------------------------------------

    def is_healthy(self) -> bool:
        """Check if server is healthy"""
        return self.is_alive()

    # -----------------------------------------------------------------------------

    def publish(self, origin: ModuleOrigin, routing_key: RoutingKey, data: Dict or None):
        """Publish message to all clients"""
        raw_message: dict = {
            "routing_key": routing_key.value,
            "origin": origin.value,
            "data": data,
        }

        message = json.dumps(raw_message)

        for client in self.__connections.values():
            client.publish(message)

        self.__logger.debug(
            "Successfully published message to: %d clients via WS server with key: %s",
            len(self.__connections),
            routing_key
        )

    # -----------------------------------------------------------------------------

    def __handle_request(self) -> None:
        writers = []

        for fileno in self.__listeners:
            if fileno == self.__server_socket:
                continue

            if fileno not in self.__connections:
                continue

            client = self.__connections[fileno]

            if client.get_send_queue():
                writers.append(fileno)

        if self.__select_interval:
            r_list, w_list, x_list = select.select(  # pylint: disable=c-extension-no-member
                self.__listeners,
                writers,
                self.__listeners,
                self.__select_interval,
            )

        else:
            r_list, w_list, x_list = select.select(  # pylint: disable=c-extension-no-member
                self.__listeners,
                writers,
                self.__listeners,
            )

        for ready in w_list:
            client = self.__connections[ready]

            try:
                while client.get_send_queue():
                    opcode, payload = client.get_send_queue().popleft()
                    remaining = client.send_buffer(payload)

                    if remaining is not None:
                        client.get_send_queue().appendleft((opcode, remaining))
                        break

                    if opcode == OPCode(OPCode.CLOSE).value:
                        raise ClientException("Received client close")

            except (ClientException, HandleResponseException):
                self.__handle_close(client)

                del self.__connections[ready]

                self.__listeners.remove(ready)

        for ready in r_list:
            if ready == self.__server_socket:
                sock, address = self.__server_socket.accept()

                client_socket = self.__decorate_socket(sock)
                client_socket.setblocking(False)

                fileno = client_socket.fileno()

                self.__connections[fileno] = WampClient(
                    client_socket,
                    address,
                    self.__handle_client_subscribed,
                    self.__handle_client_unsubscribed,
                    self.__handle_rpc_message,
                    self.__logger,
                )

                self.__listeners.append(fileno)

            else:
                if ready not in self.__connections:
                    continue

                client = self.__connections[ready]

                try:
                    client.receive_data()

                except (HandleDataException, HandleRequestException, HandleResponseException):
                    self.__handle_close(client)

                    del self.__connections[ready]

                    self.__listeners.remove(ready)

        for failed in x_list:
            if failed == self.__server_socket:
                self.close()

                raise Exception("Server socket failed")

            if failed not in self.__connections:
                continue

            client = self.__connections[failed]

            self.__handle_close(client)

            del self.__connections[failed]

            self.__listeners.remove(failed)

    # -----------------------------------------------------------------------------

    def __decorate_socket(self, sock: socket.socket) -> socket.socket:
        if self.__using_ssl:
            return self.__secured_context.wrap_socket(sock, server_side=True)

        return sock

    # -----------------------------------------------------------------------------

    @staticmethod
    def __handle_close(client: WampClient):
        client.sock.close()

        # only call handle_close when we have a successful websocket connection
        if client.is_handshake_finished():
            try:
                client.close()

            except HandleDataException:
                pass

    # -----------------------------------------------------------------------------

    def __handle_client_subscribed(self, client: WampClient) -> None:
        self.__event_dispatcher.dispatch(
            ClientSubscribedEvent.EVENT_NAME,
            ClientSubscribedEvent(
                client_id=client.get_id(),
            ),
        )

    # -----------------------------------------------------------------------------

    def __handle_client_unsubscribed(self, client: WampClient) -> None:
        self.__event_dispatcher.dispatch(
            ClientUnsubscribedEvent.EVENT_NAME,
            ClientUnsubscribedEvent(
                client_id=client.get_id(),
            ),
        )

    # -----------------------------------------------------------------------------

    def __handle_rpc_message(self, origin: ModuleOrigin, routing_key: RoutingKey, data: Dict or None) -> None:
        if self.__exchange_consumer is not None:
            self.__exchange_consumer.consume(origin=origin, routing_key=routing_key, data=data)

        self.__event_dispatcher.dispatch(
            MessageReceivedEvent.EVENT_NAME,
            MessageReceivedEvent(
                origin=origin,
                routing_key=routing_key,
                data=data,
            )
        )

    # -----------------------------------------------------------------------------

    def __iter__(self) -> "WebsocketsServer":
        # Reset index for nex iteration
        self.__iterator_index = 0

        return self

    # -----------------------------------------------------------------------------

    def __len__(self):
        return len(self.__connections)

    # -----------------------------------------------------------------------------

    def __next__(self) -> WampClient:
        if self.__iterator_index < len(self.__connections):
            clients = list(self.__connections.values())

            result: WampClient = clients[self.__iterator_index]

            self.__iterator_index += 1

            return result

        # Reset index for nex iteration
        self.__iterator_index = 0

        # End of iteration
        raise StopIteration
