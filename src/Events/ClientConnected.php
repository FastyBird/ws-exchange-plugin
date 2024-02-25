<?php declare(strict_types = 1);

/**
 * ClientConnected.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           15.01.22
 */

namespace FastyBird\Plugin\WsServer\Events;

use IPub\WebSockets;

/**
 * WS client connected to server event
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class ClientConnected
{

	public function __construct(
		private WebSockets\Entities\Clients\IClient $client,
		private WebSockets\Http\IRequest $httpRequest,
	)
	{
	}

	public function getClient(): WebSockets\Entities\Clients\IClient
	{
		return $this->client;
	}

	public function getHttpRequest(): WebSockets\Http\IRequest
	{
		return $this->httpRequest;
	}

}
