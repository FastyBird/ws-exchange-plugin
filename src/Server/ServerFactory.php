<?php declare(strict_types = 1);

/**
 * ServerSubscriber.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           21.12.20
 */

namespace FastyBird\WsServerPlugin\Server;

use FastyBird\Exchange\Consumer as ExchangeConsumer;
use FastyBird\WsServerPlugin\Consumers;
use IPub\WebSockets;
use Nette\Utils;
use Psr\Log;
use React\Socket;
use Throwable;

/**
 * WS server factory
 *
 * @package         FastyBird:WsServerPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ServerFactory
{

	/** @var WebSockets\Server\Handlers */
	private WebSockets\Server\Handlers $handlers;

	/** @var WebSockets\Server\Configuration */
	private WebSockets\Server\Configuration $configuration;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		WebSockets\Server\Handlers $handlers,
		WebSockets\Server\Configuration $configuration,
		Consumers\Consumer $exchangeConsumer,
		ExchangeConsumer\Consumer $consumer,
		?Log\LoggerInterface $logger = null
	) {
		$this->handlers = $handlers;
		$this->configuration = $configuration;

		$this->logger = $logger ?? new Log\NullLogger();

		$consumer->register($exchangeConsumer);
	}

	/**
	 * @param Socket\ServerInterface $server
	 *
	 * @return void
	 */
	public function create(Socket\ServerInterface $server): void
	{
		$server->on('connection', function (Socket\ConnectionInterface $connection): void {
			if ($connection->getLocalAddress() === null) {
				return;
			}

			$parsed = Utils\ArrayHash::from((array) parse_url($connection->getLocalAddress()));

			if (
				$parsed->offsetExists('port')
				&& $parsed->offsetGet('port') === $this->configuration->getPort()
			) {
				$this->handlers->handleConnect($connection);
			}
		});

		$server->on('error', function (Throwable $ex): void {
			$this->logger->error('Could not establish connection', [
				'source'    => 'ws-server-plugin',
				'type'      => 'server',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);
		});

		$this->logger->debug('Launching WebSockets WS Server', [
			'source' => 'ws-server-plugin',
			'type'   => 'server',
			'server' => [
				'address' => $this->configuration->getAddress(),
				'port'    => $this->configuration->getPort(),
			],
		]);
	}

}
