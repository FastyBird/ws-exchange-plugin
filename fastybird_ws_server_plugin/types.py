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
WS server plugin enums and types
"""

# Python base dependencies
from enum import Enum, unique


@unique
class OPCode(Enum):
    """
    Sockets OP code

    @package        FastyBird:WsServerPlugin!
    @module         types

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    STREAM: int = 0x00
    TEXT: int = 0x01
    BINARY: int = 0x02
    CLOSE: int = 0x08
    PING: int = 0x09
    PONG: int = 0x0A

    # -----------------------------------------------------------------------------

    @classmethod
    def has_value(cls, value: int) -> bool:
        """Check if provided value is valid enum value"""
        return value in cls._value2member_map_  # pylint: disable=no-member


@unique
class WampCode(Enum):
    """
    WAMP message code

    @package        FastyBird:WsServerPlugin!
    @module         types

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    MSG_WELCOME: int = 0
    MSG_PREFIX: int = 1
    MSG_CALL: int = 2
    MSG_CALL_RESULT: int = 3
    MSG_CALL_ERROR: int = 4
    MSG_SUBSCRIBE: int = 5
    MSG_UNSUBSCRIBE: int = 6
    MSG_PUBLISH: int = 7
    MSG_EVENT: int = 8

    # -----------------------------------------------------------------------------

    @classmethod
    def has_value(cls, value: int) -> bool:
        """Check if provided value is valid enum value"""
        return value in cls._value2member_map_  # pylint: disable=no-member
