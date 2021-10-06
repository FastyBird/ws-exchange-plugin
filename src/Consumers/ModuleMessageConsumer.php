<?php declare(strict_types = 1);

/**
 * ModuleMessageConsumer.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Consumers
 * @since          0.1.0
 *
 * @date           15.08.20
 */

namespace FastyBird\WsServerPlugin\Consumers;

use FastyBird\ExchangePlugin\Consumer as ExchangePluginConsumer;
use FastyBird\ModulesMetadata;
use FastyBird\WsServerPlugin\Exceptions;
use FastyBird\WsServerPlugin\Sockets;
use Nette;
use Nette\Utils;
use Psr\Log;

/**
 * Auth node message consumer
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ModuleMessageConsumer implements ExchangePluginConsumer\IConsumer
{

	use Nette\SmartObject;

	private const ROUTING_KEYS = [
		ModulesMetadata\Constants::MESSAGE_BUS_ACCOUNTS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_ACCOUNTS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_ACCOUNTS_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_EMAILS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_EMAILS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_EMAILS_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_IDENTITIES_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_IDENTITIES_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_IDENTITIES_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_CONNECTOR_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_CONNECTOR_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_CONNECTOR_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_CONFIGURATION_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_CONFIGURATION_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_CONFIGURATION_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_PROPERTY_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_PROPERTY_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_PROPERTY_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_CONFIGURATION_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_CONFIGURATION_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_CONFIGURATION_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_PROPERTY_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_PROPERTY_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_PROPERTY_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_CONNECTORS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_CONNECTORS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_CONNECTORS_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_ACTIONS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_ACTIONS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_ACTIONS_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_NOTIFICATIONS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_NOTIFICATIONS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_NOTIFICATIONS_DELETED_ENTITY_ROUTING_KEY,

		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_CONDITIONS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_CONDITIONS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MESSAGE_BUS_TRIGGERS_CONDITIONS_DELETED_ENTITY_ROUTING_KEY,
	];

	/** @var Sockets\ISender */
	private Sockets\ISender $sender;

	/** @var Log\LoggerInterface */
	protected Log\LoggerInterface $logger;

	public function __construct(
		Sockets\ISender $sender,
		?Log\LoggerInterface $logger = null
	) {
		$this->sender = $sender;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function consume(
		ModulesMetadata\Types\ModuleOriginType $origin,
		ModulesMetadata\Types\RoutingKeyType $routingKey,
		Utils\ArrayHash $message
	): void {
		if (!in_array($routingKey->getValue(), self::ROUTING_KEYS, true)) {
			throw new Exceptions\InvalidStateException('Unknown routing key');
		}

		$result = $this->sender->sendEntity(
			'Exchange:',
			[
				'routing_key' => $routingKey->getValue(),
				'origin'      => $origin->getValue(),
				'data'        => $this->dataToArray($message),
			]
		);

		if ($result) {
			$this->logger->info('[FB:PLUGIN:WS_SERVER] Successfully consumed entity message', [
				'routing_key' => $routingKey->getValue(),
				'origin'      => $origin->getValue(),
				'data'        => $this->dataToArray($message),
			]);
		}
	}

	/**
	 * @param Utils\ArrayHash $data
	 *
	 * @return mixed[]
	 */
	private function dataToArray(Utils\ArrayHash $data): array
	{
		$transformed = (array) $data;

		foreach ($transformed as $key => $value) {
			if ($value instanceof Utils\ArrayHash) {
				$transformed[$key] = $this->dataToArray($value);
			}
		}

		return $transformed;
	}

}
