<?php declare(strict_types = 1);

/**
 * IncomingMessage.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchange!
 * @subpackage     Events
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\Plugin\WsExchange\Events;

use IPub\WebSockets;

/**
 * WS client sent message event
 *
 * @package        FastyBird:WsExchange!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class IncomingMessage
{

	public function __construct(
		private readonly WebSockets\Entities\Clients\IClient $client,
		private readonly WebSockets\Http\IRequest $httpRequest,
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
