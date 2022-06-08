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

# Python base dependencies
from typing import Dict, Optional

# Library dependencies
from fastybird_metadata.routing import RoutingKey
from fastybird_metadata.types import ModuleSource

# Library libs
from fastybird_ws_server_plugin.clients import ClientsManager


class Publisher:  # pylint: disable=too-few-public-methods
    """
    Exchange data publisher

    @package        FastyBird:WsServerPlugin!
    @module         publisher

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __clients_manager: ClientsManager

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        clients_manager: ClientsManager,
    ) -> None:
        self.__clients_manager = clients_manager

    # -----------------------------------------------------------------------------

    def publish(
        self,
        origin: ModuleSource,
        routing_key: RoutingKey,
        data: Optional[Dict],
    ) -> None:
        """Publish data to connected clients"""
        self.__clients_manager.publish(origin=origin, routing_key=routing_key, data=data)
