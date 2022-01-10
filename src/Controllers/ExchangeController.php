<?php declare(strict_types = 1);

/**
 * ExchangeController.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           01.05.20
 */

namespace FastyBird\WsServerPlugin\Controllers;

use FastyBird\Metadata;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Loaders as MetadataLoaders;
use FastyBird\Metadata\Schemas as MetadataSchemas;
use FastyBird\WsServerPlugin\Consumer;
use FastyBird\WsServerPlugin\Exceptions;
use IPub\WebSockets;
use Nette\Utils;
use Psr\Log;
use Throwable;

/**
 * Exchange sockets controller
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExchangeController extends WebSockets\Application\Controller\Controller
{

	/** @var Consumer\IConsumer|null */
	private ?Consumer\IConsumer $consumer;

	/** @var MetadataLoaders\ISchemaLoader */
	private MetadataLoaders\ISchemaLoader $schemaLoader;

	/** @var MetadataSchemas\IValidator */
	private MetadataSchemas\IValidator $jsonValidator;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		MetadataLoaders\ISchemaLoader $schemaLoader,
		MetadataSchemas\IValidator $jsonValidator,
		?Consumer\IConsumer $consumer = null,
		?Log\LoggerInterface $logger = null
	) {
		parent::__construct();

		$this->schemaLoader = $schemaLoader;
		$this->consumer = $consumer;
		$this->jsonValidator = $jsonValidator;
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @param mixed[] $args
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function actionCall(
		array $args
	): void {
		if (!array_key_exists('routing_key', $args) || !array_key_exists('origin', $args)) {
			throw new Exceptions\InvalidArgumentException('Provided message has invalid format');
		}

		switch ($args['routing_key']) {
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_CONFIGURATION_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_CONFIGURATION_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_TRIGGER_ACTION_ROUTING_KEY:
				$schema = $this->schemaLoader->loadByRoutingKey($args['routing_key']);
				$data = $this->parseData($args, $schema);

				if ($this->consumer !== null) {
					$this->consumer->consume(
						Metadata\Types\ModuleOriginType::get($args['origin']),
						Metadata\Types\RoutingKeyType::get($args['routing_key']),
						$data,
					);
				}

				break;

			default:
				throw new Exceptions\InvalidArgumentException('Provided message has unsupported routing key');
		}

		$this->payload->data = [
			'response' => 'accepted',
		];
	}

	/**
	 * @param mixed[] $data
	 * @param string $schema
	 *
	 * @return Utils\ArrayHash
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	private function parseData(
		array $data,
		string $schema
	): Utils\ArrayHash {
		try {
			return $this->jsonValidator->validate(Utils\Json::encode($data), $schema);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('[FB:PLUGIN:WS_SERVER] Received message could not be validated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data are not valid json format', 0, $ex);

		} catch (MetadataExceptions\InvalidDataException $ex) {
			$this->logger->debug('[FB:PLUGIN:WS_SERVER] Received message is not valid', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data are not in valid structure', 0, $ex);

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:WS_SERVER] Received message is not valid', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data could not be validated', 0, $ex);
		}
	}

}
