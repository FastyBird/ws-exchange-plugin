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
Connected WAMP client
"""

# Library dependencies
import base64
import codecs
import errno
import hashlib
import json
import logging
import socket
import struct
import sys
import random
import time
from codecs import IncrementalDecoder
from collections import deque
from io import BytesIO
from http.client import parse_headers, HTTPMessage
from typing import Callable, Dict, List, Union, Tuple
import modules_metadata.exceptions as metadata_exceptions
from modules_metadata.loader import load_schema
from modules_metadata.routing import RoutingKey
from modules_metadata.validator import validate
from modules_metadata.types import ModuleOrigin

# Library libs
from ws_server_plugin.exceptions import (
    HandleDataException,
    HandleRpcException,
    HandleRequestException,
    HandleResponseException,
)
from ws_server_plugin.types import OPCode, WampCodes


class WampClient:
    """
    Web socket WAMP client

    @package        FastyBird:WsServerPlugin!
    @module         client

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """
    __handshake_finished: bool = False
    __request_header_buffer: bytearray = bytearray()
    __request_header_parsed: HTTPMessage or None = None

    __fin: int = 0
    __received_data: bytearray = bytearray()
    __opcode: int = 0
    __has_mask: int = 0
    __mask_array: bytearray or None = None
    __length: int = 0
    __length_array: bytearray or None = None
    __index: int = 0

    __frag_start: bool = False
    __frag_type: int = OPCode(OPCode.BINARY).value
    __frag_buffer: bytearray or None = None
    __frag_decoder: IncrementalDecoder = codecs.getincrementaldecoder("utf-8")(errors="strict")

    __is_closed: bool = False

    __send_queue: deque = deque()

    __state: int

    __prefixes: Dict[str, str] = {}

    __subscribe_callback: Callable[["WampClient"], None] or None = None
    __unsubscribe_callback: Callable[["WampClient"], None] or None = None
    __rpc_callback: Callable[[ModuleOrigin, RoutingKey, Dict or None], None] or None = None

    __logger: logging.Logger

    __HEADER_B1: int = 1
    __HEADER_B2: int = 3
    __LENGTH_SHORT: int = 4
    __LENGTH_LONG: int = 5
    __MASK: int = 6
    __PAYLOAD: int = 7

    __MAX_HEADER: int = 65536
    __MAX_PAYLOAD: int = 33554432
    __HEADER_SIZE: int = 2048

    __VALID_STATUS_CODES: List[int] = [
        1000,
        1001,
        1002,
        1003,
        1007,
        1008,
        1009,
        1010,
        1011,
        3000,
        3999,
        4000,
        4999,
    ]

    __GUID_STR: str = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"

    # -----------------------------------------------------------------------------

    __HANDSHAKE_STR = (
        "HTTP/1.1 101 Switching Protocols\r\n"
        "Upgrade: WebSocket\r\n"
        "Connection: Upgrade\r\n"
        "Sec-WebSocket-Accept: %(accept__str)s\r\n\r\n"
    )

    # -----------------------------------------------------------------------------

    __FAILED_HANDSHAKE_STR = (
        "HTTP/1.1 426 Upgrade Required\r\n"
        "Upgrade: WebSocket\r\n"
        "Connection: Upgrade\r\n"
        "Sec-WebSocket-Version: 13\r\n"
        "Content-Type: text/plain\r\n\r\n"
        "This service requires use of the WebSocket protocol\r\n"
    )

    __WS_SERVER_TOPIC: str = "/io/exchange"

    __ALLOWED_ROUTING_KEYS: List[RoutingKey] = [
        RoutingKey.DEVICES_CONTROL_ENTITY_DATA_ROUTING_KEY,
        RoutingKey.DEVICES_PROPERTIES_DATA_ROUTING_KEY,
        RoutingKey.DEVICES_CONFIGURATION_DATA_ROUTING_KEY,
        RoutingKey.CHANNELS_CONTROL_ENTITY_DATA_ROUTING_KEY,
        RoutingKey.CHANNELS_PROPERTIES_DATA_ROUTING_KEY,
        RoutingKey.CHANNELS_CONFIGURATION_DATA_ROUTING_KEY,
        RoutingKey.CONNECTORS_CONTROL_ENTITY_DATA_ROUTING_KEY,
        RoutingKey.TRIGGERS_CONTROL_ENTITY_DATA_ROUTING_KEY,
    ]

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        sock: socket.socket,
        address: Tuple[str, int, int, int],
        subscribe_callback: Callable[["WampClient"], None] or None = None,
        unsubscribe_callback: Callable[["WampClient"], None] or None = None,
        rpc_callback: Callable[[ModuleOrigin, RoutingKey, Dict or None], None] or None = None,
        logger: logging.Logger or None = None,
    ) -> None:
        self.sock: socket.socket = sock
        self.address: Tuple[str, int, int, int] = address

        self.__state: int = self.__HEADER_B1

        self.__request_header_buffer = bytearray()

        self.__wamp_session = (
            str(random.randint(0, sys.maxsize))
            + hex(int(time.time()))[2:10]
            + hex(int(time.time() * 1000000) % 0x100000)[2:7]
        ).replace(".", "")

        self.__subscribe_callback = subscribe_callback
        self.__unsubscribe_callback = unsubscribe_callback
        self.__rpc_callback = rpc_callback

        self.__logger = logger or logging.getLogger("dummy")

    # -----------------------------------------------------------------------------

    def get_id(self) -> str:
        """
        Get client unique identifier
        """
        return self.__wamp_session

    # -----------------------------------------------------------------------------

    def publish(self, message: str) -> None:
        """
        Send data frame to the client
        """
        data: str = json.dumps(
            [
                WampCodes(WampCodes.MSG_EVENT).value,
                self.__WS_SERVER_TOPIC,
                message,
            ]
        )

        self.__send_message(False, OPCode(OPCode.TEXT), data)

    # -----------------------------------------------------------------------------

    def close(self, status: int = 1000, reason: str = u"") -> None:
        """
        Send Close frame to the client. The underlying socket is only closed
        when the client acknowledges the Close frame.
        status is the closing identifier.
        reason is the reason for the close.
        """
        try:
            if self.__is_closed is False:
                close_msg = bytearray()
                close_msg.extend(struct.pack("!H", status))  # pylint: disable=no-member

                if isinstance(reason, str):
                    close_msg.extend(reason.encode("utf-8"))

                else:
                    close_msg.extend(reason)

                self.__send_message(False, OPCode(OPCode.CLOSE), close_msg)

        finally:
            self.__is_closed = True

    # -----------------------------------------------------------------------------

    def is_handshake_finished(self) -> bool:
        """
        Flag informing that client has finished handshake process
        """
        return self.__handshake_finished

    # -----------------------------------------------------------------------------

    def receive_data(self) -> None:
        """
        Process received frame
        """
        # Do the HTTP header and handshake
        if self.__handshake_finished is False:
            data = self.sock.recv(self.__HEADER_SIZE)

            if not data:
                raise HandleRequestException("Remote socket closed")

            # accumulate
            self.__request_header_buffer.extend(data)

            if len(self.__request_header_buffer) >= self.__MAX_HEADER:
                raise HandleRequestException("Header exceeded allowable size")

            # indicates end of HTTP header
            if b"\r\n\r\n" in self.__request_header_buffer:
                # handshake rfc 6455
                try:
                    header_buffer = BytesIO(self.__request_header_buffer)
                    header_buffer.readline()

                    self.__request_header_parsed = parse_headers(header_buffer)

                    key = self.__request_header_parsed.get("sec-websocket-key")
                    k = key.encode("ascii") + self.__GUID_STR.encode("ascii")
                    k_s = base64.b64encode(hashlib.sha1(k).digest()).decode("ascii")
                    handshake = self.__HANDSHAKE_STR % {"accept__str": k_s}

                    self.__send_queue.append((OPCode(OPCode.BINARY).value, handshake.encode("ascii")))

                    self.__handshake_finished = True

                    self.__send_message(
                        False,
                        OPCode(OPCode.TEXT),
                        json.dumps(
                            [
                                WampCodes(WampCodes.MSG_WELCOME).value,
                                self.__wamp_session,
                                1,
                                "FB/WebSockets/1.0.0",
                            ]
                        ),
                    )

                except Exception as ex:
                    handshake = self.__FAILED_HANDSHAKE_STR

                    self.send_buffer(handshake.encode("ascii"), True)
                    self.sock.close()

                    raise HandleRequestException(f"Handshake failed: {ex}") from ex

        else:
            data = self.sock.recv(16384)

            if not data:
                raise HandleRequestException("Remote socket closed")

            for data_row in data:
                self.__process_message(data_row)

    # -----------------------------------------------------------------------------

    def send_buffer(self, buff: bytes, send_all: bool = False) -> Union[int, bytes] or None:
        """
        Send buffer content to client
        """
        size = len(buff)
        to_send = size
        already_sent = 0

        while to_send > 0:
            try:
                # i should be able to send a bytearray
                sent = self.sock.send(buff[already_sent:])

                if sent == 0:
                    raise RuntimeError("Socket connection broken")

                already_sent += sent
                to_send -= sent

            except socket.error as ex:  # pylint: disable=no-member
                # if we have full buffers then wait for them to drain and try again
                if ex.errno in [errno.EAGAIN, errno.EWOULDBLOCK]:
                    if send_all:
                        continue

                    return buff[already_sent:]

                raise HandleResponseException("Output buffer could not be sent to client") from ex

        return None

    # -----------------------------------------------------------------------------

    def get_send_queue(self) -> deque:
        """
        Get client payload queue
        """
        return self.__send_queue

    # -----------------------------------------------------------------------------

    def __process_message(self, byte) -> None:
        # read in the header
        if self.__state == self.__HEADER_B1:
            self.__fin = byte & 0x80
            self.__opcode = byte & 0x0F
            self.__state = self.__HEADER_B2

            self.__index = 0
            self.__length = 0
            self.__length_array = bytearray()
            self.__received_data = bytearray()

            rsv = byte & 0x70

            if rsv != 0:
                raise HandleDataException("RSV bit must be 0")

        elif self.__state == self.__HEADER_B2:
            mask = byte & 0x80
            length = byte & 0x7F

            if self.__opcode == OPCode(OPCode.PING).value and length > 125:
                raise HandleDataException("Ping packet is too large")

            self.__has_mask = mask == 128

            if length <= 125:
                self.__length = length

                # if we have a mask we must read it
                if self.__has_mask is True:
                    self.__mask_array = bytearray()
                    self.__state = self.__MASK

                else:
                    # if there is no mask and no payload we are done
                    if self.__length <= 0:
                        try:
                            self.__handle_packet()

                        finally:
                            self.__state = self.__HEADER_B1
                            self.__received_data = bytearray()

                    # we have no mask and some payload
                    else:
                        # self.index = 0
                        self.__received_data = bytearray()
                        self.__state = self.__PAYLOAD

            elif length == 126:
                self.__length_array = bytearray()
                self.__state = self.__LENGTH_SHORT

            elif length == 127:
                self.__length_array = bytearray()
                self.__state = self.__LENGTH_LONG

        elif self.__state == self.__LENGTH_SHORT:
            self.__length_array.append(byte)

            if len(self.__length_array) > 2:
                raise HandleDataException("Short length exceeded allowable size")

            if len(self.__length_array) == 2:
                self.__length = struct.unpack_from("!H", self.__length_array)[0]  # pylint: disable=no-member

                if self.__has_mask is True:
                    self.__mask_array = bytearray()
                    self.__state = self.__MASK

                else:
                    # if there is no mask and no payload we are done
                    if self.__length <= 0:
                        try:
                            self.__handle_packet()

                        finally:
                            self.__state = self.__HEADER_B1
                            self.__received_data = bytearray()

                    # we have no mask and some payload
                    else:
                        # self.index = 0
                        self.__received_data = bytearray()
                        self.__state = self.__PAYLOAD

        elif self.__state == self.__LENGTH_LONG:
            self.__length_array.append(byte)

            if len(self.__length_array) > 8:
                raise HandleDataException("Long length exceeded allowable size")

            if len(self.__length_array) == 8:
                self.__length = struct.unpack_from("!Q", self.__length_array)[0]  # pylint: disable=no-member

                if self.__has_mask is True:
                    self.__mask_array = bytearray()
                    self.__state = self.__MASK

                else:
                    # if there is no mask and no payload we are done
                    if self.__length <= 0:
                        try:
                            self.__handle_packet()

                        finally:
                            self.__state = self.__HEADER_B1
                            self.__received_data = bytearray()

                    # we have no mask and some payload
                    else:
                        # self.index = 0
                        self.__received_data = bytearray()
                        self.__state = self.__PAYLOAD

        # MASK STATE
        elif self.__state == self.__MASK:
            self.__mask_array.append(byte)

            if len(self.__mask_array) > 4:
                raise HandleDataException("Mask exceeded allowable size")

            if len(self.__mask_array) == 4:
                # if there is no mask and no payload we are done
                if self.__length <= 0:
                    try:
                        self.__handle_packet()

                    finally:
                        self.__state = self.__HEADER_B1
                        self.__received_data = bytearray()

                # we have no mask and some payload
                else:
                    # self.index = 0
                    self.__received_data = bytearray()
                    self.__state = self.__PAYLOAD

        # PAYLOAD STATE
        elif self.__state == self.__PAYLOAD:
            if self.__has_mask is True:
                self.__received_data.append(byte ^ self.__mask_array[self.__index % 4])

            else:
                self.__received_data.append(byte)

            # if length exceeds allowable size then we except and remove the connection
            if len(self.__received_data) >= self.__MAX_PAYLOAD:
                raise HandleDataException("Payload exceeded allowable size")

            # check if we have processed length bytes; if so we are done
            if (self.__index + 1) == self.__length:
                try:
                    self.__handle_packet()

                finally:
                    # self.index = 0
                    self.__state = self.__HEADER_B1
                    self.__received_data = bytearray()

            else:
                self.__index += 1

    # -----------------------------------------------------------------------------

    def __handle_packet(self) -> None:
        """
        Unpack packet content
        """
        if self.__opcode in (OPCode(OPCode.PONG).value, OPCode(OPCode.PING).value):
            if len(self.__received_data) > 125:
                raise HandleDataException("Control frame length can not be > 125")

        elif OPCode.has_value(self.__opcode) is False:
            # unknown or reserved opcode so just close
            raise HandleDataException("Unknown opcode")

        if self.__opcode == OPCode(OPCode.CLOSE).value:
            status = 1000
            reason = u""
            length = len(self.__received_data)

            if length == 0:
                pass

            elif length >= 2:
                status = struct.unpack_from("!H", self.__received_data[:2])[0]  # pylint: disable=no-member
                reason = self.__received_data[2:]

                if status not in self.__VALID_STATUS_CODES:
                    status = 1002

                if reason:
                    try:
                        reason = reason.decode("utf8", errors="strict")

                    except UnicodeDecodeError:
                        status = 1002

            else:
                status = 1002

            self.close(status, reason)

        elif self.__fin == 0:
            if self.__opcode != OPCode(OPCode.STREAM).value:
                if self.__opcode in (OPCode(OPCode.PING).value, OPCode(OPCode.PONG).value):
                    raise HandleDataException("Control messages can not be fragmented")

                self.__frag_type = self.__opcode
                self.__frag_start = True
                self.__frag_decoder.reset()

                if self.__frag_type == OPCode(OPCode.TEXT).value:
                    self.__frag_buffer = bytearray()

                    utf_str = self.__frag_decoder.decode(self.__received_data, final=False)

                    if utf_str:
                        self.__frag_buffer.append(int(utf_str))

                else:
                    self.__frag_buffer = bytearray()
                    self.__frag_buffer.extend(self.__received_data)

            else:
                if self.__frag_start is False:
                    raise HandleDataException("Fragmentation protocol error")

                if self.__frag_type == OPCode(OPCode.TEXT).value:
                    utf_str = self.__frag_decoder.decode(self.__received_data, final=False)

                    if utf_str:
                        self.__frag_buffer.append(int(utf_str))

                else:
                    self.__frag_buffer.extend(self.__received_data)

        else:
            if self.__opcode == OPCode(OPCode.STREAM).value:
                if self.__frag_start is False:
                    raise HandleDataException("Fragmentation protocol error")

                if self.__frag_type == OPCode(OPCode.TEXT).value:
                    utf_str = self.__frag_decoder.decode(self.__received_data, final=True)

                    self.__frag_buffer.append(int(utf_str))

                    self.__received_data = bytearray()
                    self.__received_data.extend(u"".join(self.__frag_buffer))

                else:
                    self.__frag_buffer.extend(self.__received_data)

                    self.__received_data = self.__frag_buffer

                self.__handle_message(self.__received_data)

                self.__frag_decoder.reset()
                self.__frag_type = OPCode(OPCode.BINARY).value
                self.__frag_start = False
                self.__frag_buffer = None

            elif self.__opcode == OPCode(OPCode.PING).value:
                self.__send_message(False, OPCode(OPCode.PONG), self.__received_data)

            elif self.__opcode == OPCode(OPCode.PONG).value:
                pass

            else:
                if self.__frag_start is True:
                    raise HandleDataException("Fragmentation protocol error")

                self.__handle_message(self.__received_data)

    # -----------------------------------------------------------------------------

    def __handle_message(self, received_data: bytearray) -> None:
        """
        Called when websocket frame is received
        """
        try:
            parsed_data: dict = json.loads(received_data)

            if int(parsed_data[0]) == WampCodes(WampCodes.MSG_PREFIX).value:
                self.__handle_wamp_prefix(parsed_data)

            # RPC from client
            elif int(parsed_data[0]) == WampCodes(WampCodes.MSG_CALL).value:
                self.__handle_wamp_call(parsed_data)

            # Subscribe client to defined topic
            elif int(parsed_data[0]) == WampCodes(WampCodes.MSG_SUBSCRIBE).value:
                self.__handle_wamp_subscribe(parsed_data)

            # Unsubscribe client from defined topic
            elif int(parsed_data[0]) == WampCodes(WampCodes.MSG_UNSUBSCRIBE).value:
                self.__handle_wamp_unsubscribe(parsed_data)

            elif int(parsed_data[0]) == WampCodes(WampCodes.MSG_PUBLISH).value:
                self.__handle_wamp_publish(parsed_data)

            else:
                self.close(1007, "Invalid WAMP message type")

        except json.JSONDecodeError:
            self.close(1007)

    # -----------------------------------------------------------------------------

    def __send_message(self, fin: bool, opcode: OPCode, data: bytearray or str) -> None:
        """
        Append payload to client buffer
        """
        payload = bytearray()

        first_byte = 0
        second_byte = 0

        if fin is False:
            first_byte |= 0x80

        first_byte |= opcode.value

        if isinstance(data, str):
            data = data.encode("utf-8")

        length = len(data)
        payload.append(first_byte)

        if length <= 125:
            second_byte |= length
            payload.append(second_byte)

        elif 126 <= length <= 65535:
            second_byte |= 126
            payload.append(second_byte)
            payload.extend(struct.pack("!H", length))  # pylint: disable=no-member

        else:
            second_byte |= 127
            payload.append(second_byte)
            payload.extend(struct.pack("!Q", length))  # pylint: disable=no-member

        if length > 0:
            payload.extend(data)

        self.__send_queue.append((opcode.value, payload))

    # -----------------------------------------------------------------------------

    def __handle_wamp_prefix(self, parsed_data: Dict) -> None:
        """
        Handle client set prefix message request
        """
        self.__prefixes[str(parsed_data[1])] = str(parsed_data[2])

        self.__send_message(
            False,
            OPCode(OPCode.TEXT),
            json.dumps(
                [
                    WampCodes(WampCodes.MSG_PREFIX).value,
                    parsed_data[1],
                    str(parsed_data[2]),
                ]
            ),
        )

    # -----------------------------------------------------------------------------

    def __handle_wamp_call(self, parsed_data: Dict) -> None:
        """
        Handle client RPC message request
        """
        parsed_data.pop(0)

        rpc_id = str(parsed_data.pop(0))
        topic_id = str(parsed_data.pop(0))

        if topic_id != self.__WS_SERVER_TOPIC:
            self.__reply_rpc_error(
                rpc_id,
                topic_id,
                "Invalid topic provided",
                json.dumps(parsed_data),
            )

            return

        # RPC callback have to be configured
        if self.__rpc_callback is None:
            self.__reply_rpc_error(
                rpc_id,
                topic_id,
                "Server has not configured RPC callback",
            )

            self.__logger.error("RPC callback is not configured. RPC could not be handled")

            return

        if len(parsed_data) == 1:
            parsed_data = parsed_data[0]

        if (
            isinstance(parsed_data, Dict) is False
            or "routing_key" not in parsed_data
            or RoutingKey.has_value(parsed_data.get("routing_key")) is False
            or "origin" not in parsed_data
            or ModuleOrigin.has_value(parsed_data.get("origin")) is False
        ):
            self.__reply_rpc_error(
                rpc_id,
                topic_id,
                "Invalid message data format provided",
                json.dumps(parsed_data),
            )

            return

        # Transform message routing key
        message_routing_key: RoutingKey = RoutingKey(parsed_data.get("routing_key"))
        # Transform message origin
        message_origin: ModuleOrigin = ModuleOrigin(parsed_data.get("origin"))
        # Just prepare variable
        message_data: Dict or None = parsed_data.get("data", None)

        if "data" in parsed_data:
            try:
                message_data = self.__validate_rpc_data(
                    message_origin,
                    message_routing_key,
                    message_data,
                )

            except HandleRpcException as ex:
                self.__reply_rpc_error(
                    rpc_id,
                    topic_id,
                    str(ex),
                )

                return

        self.__rpc_callback(
            message_origin,
            message_routing_key,
            message_data,
        )

        self.__send_message(
            False,
            OPCode(OPCode.TEXT),
            json.dumps(
                [
                    WampCodes(WampCodes.MSG_CALL_RESULT).value,
                    rpc_id,
                    {
                        "response": "accepted",
                    },
                ]
            )
        )

    # -----------------------------------------------------------------------------

    def __handle_wamp_subscribe(self, parsed_data: Dict) -> None:
        """
        Handle client subscribe message request
        """
        if str(parsed_data[1]) == self.__WS_SERVER_TOPIC:
            self.__logger.debug("New client: %s has subscribed to exchanges topic", self.get_id())

            if self.__subscribe_callback is not None:
                self.__subscribe_callback(self)

        else:
            # TODO: reply error
            pass

    # -----------------------------------------------------------------------------

    def __handle_wamp_unsubscribe(self, parsed_data: Dict) -> None:
        """
        Handle client unsubscribe message request
        """
        if str(parsed_data[1]) == self.__WS_SERVER_TOPIC:
            self.__logger.debug("Client: %s has unsubscribed from exchanges topic", self.get_id())

            if self.__unsubscribe_callback is not None:
                self.__unsubscribe_callback(self)

        else:
            # TODO: reply error
            pass

    # -----------------------------------------------------------------------------

    def __handle_wamp_publish(self, parsed_data: Dict) -> None:
        """
        Handle client publish message request
        """

    # -----------------------------------------------------------------------------

    def __validate_rpc_data(self, origin: ModuleOrigin, routing_key: RoutingKey, data: Dict) -> Dict:
        """
        Validate received RPC message against defined schema
        """
        if routing_key not in self.__ALLOWED_ROUTING_KEYS:
            raise HandleRpcException("Unsupported routing key")

        try:
            schema: str = load_schema(origin, routing_key)

        except metadata_exceptions.FileNotFoundException as ex:
            self.__logger.error(
                "Schema file for origin: %s and routing key: %s could not be loaded",
                origin.value,
                routing_key.value,
            )

            raise HandleRpcException("Provided data could not be validated") from ex

        except metadata_exceptions.InvalidArgumentException as ex:
            self.__logger.error(
                "Schema file for origin: %s and routing key: %s is not configured in mapping",
                origin.value,
                routing_key.value,
            )

            raise HandleRpcException("Provided data could not be validated") from ex

        try:
            return validate(json.dumps(data), schema)

        except metadata_exceptions.MalformedInputException as ex:
            raise HandleRpcException("Provided data are not in valid json format") from ex

        except metadata_exceptions.LogicException as ex:
            self.__logger.error(
                "Schema file for origin: %s and routing key: %s could not be parsed & compiled",
                origin.value,
                routing_key.value,
            )

            raise HandleRpcException("Provided data could not be validated") from ex

        except metadata_exceptions.InvalidDataException as ex:
            raise HandleRpcException("Provided data are not valid") from ex

    # -----------------------------------------------------------------------------

    def __reply_rpc_error(self, rpc_id: str, topic_id: str, message: str, params: str or None = None) -> None:
        """
        Send RPC error result to client
        """
        self.__send_message(
            False,
            OPCode(OPCode.TEXT),
            json.dumps(
                [
                    WampCodes(WampCodes.MSG_CALL_ERROR).value,
                    rpc_id,
                    topic_id,
                    message,
                    {
                        "params": params,
                    },
                ]
            ),
        )
