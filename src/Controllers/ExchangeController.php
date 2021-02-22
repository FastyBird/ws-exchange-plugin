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

use FastyBird\ApplicationExchange\Publisher as ApplicationExchangePublisher;
use FastyBird\ModulesMetadata;
use FastyBird\ModulesMetadata\Exceptions as ModulesMetadataExceptions;
use FastyBird\ModulesMetadata\Loaders as ModulesMetadataLoaders;
use FastyBird\ModulesMetadata\Schemas as ModulesMetadataSchemas;
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

	/** @var ApplicationExchangePublisher\IPublisher */
	private ApplicationExchangePublisher\IPublisher $publisher;

	/** @var ModulesMetadataLoaders\ISchemaLoader */
	private ModulesMetadataLoaders\ISchemaLoader $schemaLoader;

	/** @var ModulesMetadataSchemas\IValidator */
	private ModulesMetadataSchemas\IValidator $jsonValidator;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		ModulesMetadataLoaders\ISchemaLoader $schemaLoader,
		ModulesMetadataSchemas\IValidator $jsonValidator,
		ApplicationExchangePublisher\IPublisher $publisher,
		?Log\LoggerInterface $logger
	) {
		parent::__construct();

		$this->schemaLoader = $schemaLoader;
		$this->publisher = $publisher;
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
		if (!array_key_exists('routing_key', $args)) {
			throw new Exceptions\InvalidArgumentException('Provided message has invalid format');
		}

		switch ($args['routing_key']) {
			case ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_PROPERTIES_DATA_ROUTING_KEY:
				$schema = $this->schemaLoader->load(ModulesMetadata\Constants::NOT_SPECIFIED_ORIGIN, $args['routing_key']);

				$data = $this->parse($args, $schema);

				if ($this->publisher !== null) {
					$this->publisher->publish(
						$args['routing_key'],
						[
							'device'   => $data->offsetGet('device'),
							'property' => $data->offsetGet('property'),
							'expected' => $data->offsetGet('expected'),
						]
					);
				}
				break;

			case ModulesMetadata\Constants::MESSAGE_BUS_DEVICES_CONFIGURATION_DATA_ROUTING_KEY:
				$schema = $this->schemaLoader->load(ModulesMetadata\Constants::NOT_SPECIFIED_ORIGIN, $args['routing_key']);

				$data = $this->parse($args, $schema);

				if ($this->publisher !== null) {
					$this->publisher->publish(
						$args['routing_key'],
						[
							'device'   => $data->offsetGet('device'),
							'name'     => $data->offsetGet('name'),
							'expected' => $data->offsetGet('expected'),
						]
					);
				}
				break;

			case ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_PROPERTIES_DATA_ROUTING_KEY:
				$schema = $this->schemaLoader->load(ModulesMetadata\Constants::NOT_SPECIFIED_ORIGIN, $args['routing_key']);

				$data = $this->parse($args, $schema);

				if ($this->publisher !== null) {
					$this->publisher->publish(
						$args['routing_key'],
						[
							'device'   => $data->offsetGet('device'),
							'channel'  => $data->offsetGet('channel'),
							'property' => $data->offsetGet('property'),
							'expected' => $data->offsetGet('expected'),
						]
					);
				}
				break;

			case ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_CONFIGURATION_DATA_ROUTING_KEY:
				$schema = $this->schemaLoader->load(ModulesMetadata\Constants::NOT_SPECIFIED_ORIGIN, $args['routing_key']);

				$data = $this->parse($args, $schema);

				if ($this->publisher !== null) {
					$this->publisher->publish(
						$args['routing_key'],
						[
							'device'   => $data->offsetGet('device'),
							'channel'  => $data->offsetGet('channel'),
							'name'     => $data->offsetGet('name'),
							'expected' => $data->offsetGet('expected'),
						]
					);
				}
				break;

			default:
				throw new Exceptions\InvalidArgumentException('Provided message has unknown routing key');
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
	private function parse(
		array $data,
		string $schema
	): Utils\ArrayHash {
		try {
			return $this->jsonValidator->validate(Utils\Json::encode($data), $schema);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('[FB:UI_MODULE:CONTROLLER] Received message could not be validated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data are not valid json format', 0, $ex);

		} catch (ModulesMetadataExceptions\InvalidDataException $ex) {
			$this->logger->debug('[FB:UI_MODULE:CONTROLLER] Received message is not valid', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data are not in valid structure', 0, $ex);

		} catch (Throwable $ex) {
			$this->logger->error('[FB:UI_MODULE:CONTROLLER] Received message is not valid', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data could not be validated', 0, $ex);
		}
	}

}
