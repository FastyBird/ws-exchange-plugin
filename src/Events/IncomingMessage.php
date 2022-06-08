<?php declare(strict_types = 1);

/**
 * IncomingMessage.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\WsServerPlugin\Events;

use IPub\WebSockets;

/**
 * WS client sent message event
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class IncomingMessage
{

	/** @var WebSockets\Entities\Clients\IClient */
	private WebSockets\Entities\Clients\IClient $client;

	/** @var WebSockets\Http\IRequest */
	private WebSockets\Http\IRequest $httpRequest;

	public function __construct(
		WebSockets\Entities\Clients\IClient $client,
		WebSockets\Http\IRequest $httpRequest
	) {
		$this->client = $client;
		$this->httpRequest = $httpRequest;
	}

	/**
	 * @return WebSockets\Entities\Clients\IClient
	 */
	public function getClient(): WebSockets\Entities\Clients\IClient
	{
		return $this->client;
	}

	/**
	 * @return WebSockets\Http\IRequest
	 */
	public function getHttpRequest(): WebSockets\Http\IRequest
	{
		return $this->httpRequest;
	}

}
