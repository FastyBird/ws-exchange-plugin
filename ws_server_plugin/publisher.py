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
WS server plugin publisher
"""

# Library libs
from typing import Dict
from exchange_plugin.publisher import IPublisher
from kink import inject
from modules_metadata.routing import RoutingKey
from modules_metadata.types import ModuleOrigin

# Library libs
from ws_server_plugin.server import WebsocketsServer


@inject
class Publisher(IPublisher):
    """
    Exchange data publisher

    @package        FastyBird:WsServerPlugin!
    @module         publisher

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """
    __server: WebsocketsServer

    # -----------------------------------------------------------------------------

    def __init__(self, server: WebsocketsServer) -> None:
        self.__server = server

    # -----------------------------------------------------------------------------

    def publish(
        self,
        origin: ModuleOrigin,
        routing_key: RoutingKey,
        data: Dict or None,
    ) -> None:
        """Publish data to connected clients"""
        self.__server.publish(origin=origin, routing_key=routing_key, data=data)
