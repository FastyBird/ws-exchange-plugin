<?php declare(strict_types = 1);

/**
 * Sender.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Sockets
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\WsServerPlugin\Sockets;

use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette;
use Psr\Log;
use Throwable;

/**
 * Websockets data sender
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Senders
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Sender implements ISender
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
	public function sendEntity(
		string $destination,
		array $data
	): bool {
		try {
			$link = $this->linkGenerator->link($destination);

			if ($this->topicsStorage->hasTopic($link)) {
				$topic = $this->topicsStorage->getTopic($link);

				$this->logger->debug('[FB:PLUGIN:WSSERVER] Broadcasting message to topic', [
					'link' => $link,
				]);

				$topic->broadcast(Nette\Utils\Json::encode($data));

				return true;
			}
		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error('[FB:PLUGIN:WSSERVER] Data could not be converted to message', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:WSSERVER] Data could not be broadcasts to clients', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);
		}

		return false;
	}

}
