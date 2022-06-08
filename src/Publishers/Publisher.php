<?php declare(strict_types = 1);

/**
 * Sender.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Publishers
 * @since          0.2.0
 *
 * @date           08.10.21
 */

namespace FastyBird\WsServerPlugin\Publishers;

use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Types as MetadataTypes;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette;
use Psr\Log;
use Throwable;

/**
 * Websockets exchange publisher
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements IPublisher
{

	use Nette\SmartObject;

	/** @var WebSockets\Router\LinkGenerator */
	private WebSockets\Router\LinkGenerator $linkGenerator;

	/** @var WebSocketsWAMP\Topics\IStorage<WebSocketsWAMP\Entities\Topics\Topic> */
	private WebSocketsWAMP\Topics\IStorage $topicsStorage;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/**
	 * @param WebSockets\Router\LinkGenerator $linkGenerator
	 * @param WebSocketsWAMP\Topics\IStorage<WebSocketsWAMP\Entities\Topics\Topic> $topicsStorage
	 * @param Log\LoggerInterface|null $logger
	 */
	public function __construct(
		WebSockets\Router\LinkGenerator $linkGenerator,
		WebSocketsWAMP\Topics\IStorage $topicsStorage,
		?Log\LoggerInterface $logger
	) {
		$this->linkGenerator = $linkGenerator;
		$this->topicsStorage = $topicsStorage;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function publish(
		$source,
		MetadataTypes\RoutingKeyType $routingKey,
		?MetadataEntities\IEntity $entity
	): void {
		$result = $this->sendMessage(
			'Exchange:',
			[
				'routing_key' => $routingKey->getValue(),
				'origin'      => $source->getValue(),
				'data'        => $entity !== null ? $entity->toArray() : null,
			]
		);

		if ($result) {
			$this->logger->debug('Successfully published message', [
				'source'  => 'ws-server-plugin',
				'type'    => 'publish',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'origin'      => $source->getValue(),
					'data'        => $entity !== null ? $entity->toArray() : null,
				],
			]);

		} else {
			$this->logger->error('Message could not be published to exchange', [
				'source'  => 'ws-server-plugin',
				'type'    => 'publish',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'origin'      => $source->getValue(),
					'data'        => $entity !== null ? $entity->toArray() : null,
				],
			]);
		}
	}

	/**
	 * @param string $destination
	 * @param mixed[] $data
	 *
	 * @return bool
	 */
	private function sendMessage(
		string $destination,
		array $data
	): bool {
		try {
			$link = $this->linkGenerator->link($destination);

			if ($this->topicsStorage->hasTopic($link)) {
				$topic = $this->topicsStorage->getTopic($link);

				$this->logger->debug('Broadcasting message to topic', [
					'source' => 'ws-server-plugin',
					'type'   => 'broadcast',
					'link'   => $link,
				]);

				$topic->broadcast(Nette\Utils\Json::encode($data));

				return true;
			}
		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error('Data could not be converted to message', [
				'source'    => 'ws-server-plugin',
				'type'      => 'broadcast',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

		} catch (Throwable $ex) {
			var_dump($ex->getMessage());
			$this->logger->error('Data could not be broadcasts to clients', [
				'source'  => 'ws-server-plugin',
				'type'    => 'broadcast',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);
		}

		return false;
	}

}
