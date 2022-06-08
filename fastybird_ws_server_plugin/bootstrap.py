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
WS server plugin DI container
"""

# pylint: disable=no-value-for-parameter

# Python base dependencies
import logging

# Library dependencies
from kink import di

# Library libs
from fastybird_ws_server_plugin.clients import ClientsManager
from fastybird_ws_server_plugin.logger import Logger
from fastybird_ws_server_plugin.publisher import Publisher
from fastybird_ws_server_plugin.server import Server


def create_container(logger: logging.Logger = logging.getLogger("dummy")) -> None:
    """Create WS server plugin services"""
    di[Logger] = Logger(logger=logger)
    di["fb-ws-server-plugin_logger"] = di[Logger]

    di[ClientsManager] = ClientsManager(logger=di[Logger])
    di["fb-ws-server-plugin_clients-manager"] = di[ClientsManager]

    di[Server] = Server(
        clients_manager=di[ClientsManager],
        logger=di[Logger],
    )
    di["fb-ws-server-plugin_server"] = di[Server]

    di[Publisher] = Publisher(clients_manager=di[ClientsManager])
    di["fb-ws-server-plugin_publisher"] = di[Publisher]
