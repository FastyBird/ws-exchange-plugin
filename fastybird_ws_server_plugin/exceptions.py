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
WS server plugin exceptions classes
"""


class HandleRequestException(Exception):
    """
    Exception raised when incoming request could not be handled

    @package        FastyBird:WsServerPlugin!
    @module         exceptions

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """


class HandleResponseException(Exception):
    """
    Exception raised when response could not be sent to client

    @package        FastyBird:WsServerPlugin!
    @module         exceptions

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """


class ClientException(Exception):
    """
    Exception raised by connected client

    @package        FastyBird:WsServerPlugin!
    @module         exceptions

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """


class HandleDataException(Exception):
    """
    Exception raised by invalid client data handling

    @package        FastyBird:WsServerPlugin!
    @module         exceptions

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """


class HandleRpcException(Exception):
    """
    Exception raised by invalid remote procedure call

    @package        FastyBird:WsServerPlugin!
    @module         exceptions

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """
