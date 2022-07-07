<?php declare(strict_types = 1);

/**
 * ClientRpcEvent.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 * @since          0.12.0
 *
 * @date           07.07.22
 */

namespace FastyBird\WsServerPlugin\Events;

use IPub\WebSocketsWAMP;

/**
 * WS client called RPC to topic event
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientRpcEvent
{

	/** @var WebSocketsWAMP\Entities\Clients\IClient */
	private WebSocketsWAMP\Entities\Clients\IClient $client;

	/** @var WebSocketsWAMP\Entities\Topics\ITopic<mixed> */
	private WebSocketsWAMP\Entities\Topics\ITopic $topic;

	/** @var mixed[] */
	private array $args;

	/**
	 * @param WebSocketsWAMP\Entities\Clients\IClient $client
	 * @param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 * @param mixed[] $args
	 */
	public function __construct(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
		array $args
	) {
		$this->client = $client;
		$this->topic = $topic;
		$this->args = $args;
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

	/**
	 * @return mixed[]
	 */
	public function getArgs(): array
	{
		return $this->args;
	}

}
