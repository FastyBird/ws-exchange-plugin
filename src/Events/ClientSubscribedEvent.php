<?php declare(strict_types = 1);

/**
 * ClientSubscribedEvent.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 * @since          0.11.0
 *
 * @date           08.06.22
 */

namespace FastyBird\WsServerPlugin\Events;

use IPub\WebSocketsWAMP;

/**
 * WS client subscribed to topic event
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientSubscribedEvent
{

	/** @var WebSocketsWAMP\Entities\Clients\IClient */
	private WebSocketsWAMP\Entities\Clients\IClient $client;

	/** @var WebSocketsWAMP\Entities\Topics\ITopic<mixed> */
	private WebSocketsWAMP\Entities\Topics\ITopic $topic;

	/**
	 * @param WebSocketsWAMP\Entities\Clients\IClient $client
	 * @param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 */
	public function __construct(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic
	) {
		$this->client = $client;
		$this->topic = $topic;
	}

	/**
	 * @return WebSocketsWAMP\Entities\Clients\IClient
	 */
	public function getClient(): WebSocketsWAMP\Entities\Clients\IClient
	{
		return $this->client;
	}

	/**
	 * @return WebSocketsWAMP\Entities\Topics\ITopic<mixed>
	 */
	public function getTopic(): WebSocketsWAMP\Entities\Topics\ITopic
	{
		return $this->topic;
	}

}
