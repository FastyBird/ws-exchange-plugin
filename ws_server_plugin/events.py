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
WS server plugin clients events
"""

# Library dependencies
from whistle import Event


class ClientSubscribedEvent(Event):  # pylint: disable=too-few-public-methods
    """
    Event fired by exchange when client is subscribed

    @package        FastyBird:WsServerPlugin!
    @module         events

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __client_id: str

    EVENT_NAME: str = "ws-server-plugin.clientSubscribed"

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        client_id: str,
    ) -> None:
        self.__client_id = client_id

    # -----------------------------------------------------------------------------

    @property
    def client_id(self) -> str:
        """Connected client identifier"""
        return self.__client_id


class ClientUnsubscribedEvent(Event):  # pylint: disable=too-few-public-methods
    """
    Event fired by exchange when client is unsubscribed

    @package        FastyBird:WsServerPlugin!
    @module         events

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __client_id: str

    EVENT_NAME: str = "ws-server-plugin.clientUnsubscribed"

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        client_id: str,
    ) -> None:
        self.__client_id = client_id

    # -----------------------------------------------------------------------------

    @property
    def client_id(self) -> str:
        """Connected client identifier"""
        return self.__client_id
