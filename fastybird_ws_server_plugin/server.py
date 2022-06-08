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

# Python base dependencies
import select
import socket
import ssl
from abc import ABC
from typing import Dict, List, Optional, Union

# Library dependencies
from fastybird_metadata.routing import RoutingKey
from fastybird_metadata.types import ModuleSource
from kink import inject
from whistle import EventDispatcher

# Library libs
from fastybird_ws_server_plugin.client import WampClient
from fastybird_ws_server_plugin.clients import ClientsManager
from fastybird_ws_server_plugin.events import (
    ClientSubscribedEvent,
    ClientUnsubscribedEvent,
)
from fastybird_ws_server_plugin.exceptions import (
    ClientException,
    HandleDataException,
    HandleRequestException,
    HandleResponseException,
)
from fastybird_ws_server_plugin.logger import Logger
from fastybird_ws_server_plugin.types import OPCode


class IConsumer(ABC):  # pylint: disable=too-few-public-methods
    """
    WS server consumer interface

    @package        FastyBird:WsServerPlugin!
    @module         consumer

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    def consume(
        self,
        origin: ModuleSource,
        routing_key: RoutingKey,
        data: Optional[Dict[str, Union[str, int, float, bool, None]]],
    ) -> None:
        """Consume data received from exchange bus"""


@inject
class Server:  # pylint: disable=too-many-instance-attributes
    """
    Main websockets server instance

    @package        FastyBird:WsServerPlugin!
    @module         server

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __request_queue_size: int = 5
    __select_interval: float = 0.1

    __using_ssl: bool = False

    __server_socket: socket.socket

    __host_info: List

    __listeners: List[Union[int, socket.socket]] = []

    __secured_context: Optional[ssl.SSLContext]

    __clients_manager: ClientsManager

    __dispatcher: Optional[EventDispatcher] = None
    __consumer: Optional[IConsumer] = None

    __logger: Logger

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        clients_manager: ClientsManager,
        logger: Logger,
        host: Optional[str] = None,
        port: int = 9000,
        cert_file: Optional[str] = None,
        key_file: Optional[str] = None,
        ssl_version: int = ssl.PROTOCOL_TLSv1,
        select_interval: float = 0.1,
        dispatcher: EventDispatcher = None,  # type: ignore[assignment]
        consumer: IConsumer = None,  # type: ignore[assignment]
    ) -> None:
        self.__clients_manager = clients_manager

        fam = socket.AF_INET6 if host is None else 0  # pylint: disable=no-member

        self.__host_info = socket.getaddrinfo(
            host,
            port,
            fam,
            socket.SOCK_STREAM,  # pylint: disable=no-member
            socket.IPPROTO_TCP,  # pylint: disable=no-member
            socket.AI_PASSIVE,  # pylint: disable=no-member
        )

        self.__select_interval = select_interval

        self.__using_ssl = bool(cert_file and key_file)

        if self.__using_ssl and cert_file and key_file:
            self.__secured_context = ssl.SSLContext(ssl_version)
            self.__secured_context.load_cert_chain(cert_file, key_file)

        self.__consumer = consumer
        self.__dispatcher = dispatcher

        self.__logger = logger

    # -----------------------------------------------------------------------------

    def start(self) -> None:
        """Start server services"""
        self.__logger.info(
            "Starting WS server",
            extra={
                "source": "ws-server-plugin-server",
                "type": "start",
            },
        )

        self.__server_socket = socket.socket(self.__host_info[0][0], self.__host_info[0][1], self.__host_info[0][2])
        self.__server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)  # pylint: disable=no-member
        self.__server_socket.bind(self.__host_info[0][4])
        self.__server_socket.listen(self.__request_queue_size)

        self.__listeners = [self.__server_socket]

    # -----------------------------------------------------------------------------

    def stop(self) -> None:
        """Close all opened connections & stop server thread"""
        for client in self.__clients_manager:
            client.close()

            self.__handle_close(client=client)

        self.__server_socket.close()

        self.__listeners = []

        self.__logger.info(
            "Closing WS server",
            extra={
                "source": "ws-server-plugin-server",
                "type": "stop",
            },
        )

    # -----------------------------------------------------------------------------

    def handle(self) -> None:  # pylint: disable=too-many-instance-attributes, too-many-statements, too-many-branches
        """Process server communication"""
        writers = []

        for fileno in self.__listeners:
            if fileno == self.__server_socket:
                continue

            if not self.__clients_manager.exists(client_id=fileno):
                continue

            client = self.__clients_manager.get_by_id(client_id=fileno)

            if client and client.get_send_queue():
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
            client = self.__clients_manager.get_by_id(ready)

            try:
                if client:
                    while client.get_send_queue():
                        opcode, payload = client.get_send_queue().popleft()

                        remaining = client.send_buffer(payload)

                        if remaining is not None:
                            client.get_send_queue().appendleft((opcode, remaining))
                            break

                        if opcode == OPCode.CLOSE.value:
                            raise ClientException("Received client close")

            except (ClientException, HandleResponseException):
                if client:
                    self.__handle_close(client=client)

                self.__clients_manager.delete(ready)

                self.__listeners.remove(ready)

        for ready in r_list:
            if ready == self.__server_socket:
                sock, address = self.__server_socket.accept()

                client_socket = self.__decorate_socket(sock)
                client_socket.setblocking(False)

                fileno = client_socket.fileno()

                self.__clients_manager.append(
                    client_id=fileno,
                    client=WampClient(
                        client_socket,
                        address,
                        self.__handle_client_subscribed,
                        self.__handle_client_unsubscribed,
                        self.__handle_rpc_message,
                        self.__logger,
                    ),
                )

                self.__listeners.append(fileno)

            else:
                if not self.__clients_manager.exists(client_id=ready):
                    continue

                client = self.__clients_manager.get_by_id(ready)

                try:
                    if client:
                        client.receive_data()

                except (HandleDataException, HandleRequestException, HandleResponseException):
                    if client:
                        self.__handle_close(client=client)

                    self.__clients_manager.delete(ready)

                    self.__listeners.remove(ready)

        for failed in x_list:
            if failed == self.__server_socket:
                self.stop()

                raise Exception("Server socket failed")

            if not self.__clients_manager.exists(client_id=failed):
                continue

            client = self.__clients_manager.get_by_id(failed)

            if client:
                self.__handle_close(client=client)

            self.__clients_manager.delete(failed)

            self.__listeners.remove(failed)

    # -----------------------------------------------------------------------------

    def __decorate_socket(self, sock: socket.socket) -> socket.socket:
        if self.__using_ssl and self.__secured_context:
            return self.__secured_context.wrap_socket(sock, server_side=True)

        return sock

    # -----------------------------------------------------------------------------

    @staticmethod
    def __handle_close(client: WampClient) -> None:
        client.sock.close()

        # only call handle_close when we have a successful websocket connection
        if client.is_handshake_finished():
            try:
                client.close()

            except HandleDataException:
                pass

    # -----------------------------------------------------------------------------

    def __handle_client_subscribed(self, client: WampClient) -> None:
        if self.__dispatcher is not None:
            self.__dispatcher.dispatch(
                ClientSubscribedEvent.EVENT_NAME,
                ClientSubscribedEvent(
                    client_id=client.get_id(),
                ),
            )

    # -----------------------------------------------------------------------------

    def __handle_client_unsubscribed(self, client: WampClient) -> None:
        if self.__dispatcher is not None:
            self.__dispatcher.dispatch(
                ClientUnsubscribedEvent.EVENT_NAME,
                ClientUnsubscribedEvent(
                    client_id=client.get_id(),
                ),
            )

    # -----------------------------------------------------------------------------

    def __handle_rpc_message(self, origin: ModuleSource, routing_key: RoutingKey, data: Optional[Dict]) -> None:
        if self.__consumer is not None:
            self.__consumer.consume(origin=origin, routing_key=routing_key, data=data)
