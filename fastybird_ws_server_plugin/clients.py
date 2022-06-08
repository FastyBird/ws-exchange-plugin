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
WS server plugin clients manager
"""

# Python base dependencies
import json
from socket import socket
from typing import Dict, Optional, Union

# Library dependencies
from fastybird_metadata.routing import RoutingKey
from fastybird_metadata.types import ModuleSource

# Library libs
from fastybird_ws_server_plugin.client import WampClient
from fastybird_ws_server_plugin.logger import Logger


class ClientsManager:
    """
    Connected clients manager

    @package        FastyBird:WsServerPlugin!
    @module         clients

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __clients: Dict[Union[int, socket], WampClient] = {}

    __logger: Logger

    __iterator_index = 0

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        logger: Logger,
    ) -> None:
        self.__clients = {}

        self.__logger = logger

    # -----------------------------------------------------------------------------

    def get_by_id(self, client_id: Union[int, socket]) -> Optional[WampClient]:
        """Get client by identifier"""
        if self.exists(client_id=client_id):
            return self.__clients[client_id]

        return None

    # -----------------------------------------------------------------------------

    def append(self, client_id: int, client: WampClient) -> None:
        """Add new client"""
        self.__clients[client_id] = client

    # -----------------------------------------------------------------------------

    def delete(self, client_id: Union[int, socket]) -> None:
        """Delete client"""
        del self.__clients[client_id]

    # -----------------------------------------------------------------------------

    def exists(self, client_id: Union[int, socket]) -> bool:
        """Check if client exists"""
        return client_id in self.__clients

    # -----------------------------------------------------------------------------

    def publish(self, origin: ModuleSource, routing_key: RoutingKey, data: Optional[Dict]) -> None:
        """Publish message to all clients"""
        raw_message = {
            "routing_key": routing_key.value,
            "origin": origin.value,
            "data": data,
        }

        message = json.dumps(raw_message)

        for client in self.__clients.values():
            client.publish(message=message)

        self.__logger.debug(
            "Successfully published message to: %d clients via WS server plugin with key: %s",
            len(self.__clients),
            routing_key,
            extra={
                "source": "ws-server-plugin-clients",
                "type": "publish",
            },
        )

    # -----------------------------------------------------------------------------

    def __iter__(self) -> "ClientsManager":
        # Reset index for nex iteration
        self.__iterator_index = 0

        return self

    # -----------------------------------------------------------------------------

    def __len__(self) -> int:
        return len(self.__clients)

    # -----------------------------------------------------------------------------

    def __next__(self) -> WampClient:
        if self.__iterator_index < len(self.__clients):
            clients = list(self.__clients.values())

            result: WampClient = clients[self.__iterator_index]

            self.__iterator_index += 1

            return result

        # Reset index for nex iteration
        self.__iterator_index = 0

        # End of iteration
        raise StopIteration
