<?php declare(strict_types = 1);

/**
 * WsServerCommand.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     common
 * @since          0.13.0
 *
 * @date           09.06.22
 */

namespace FastyBird\WsServerPlugin\Commands;

use FastyBird\SocketServerFactory;
use IPub\WebSockets;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Socket;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

/**
 * WS server command
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WsServerCommand extends Console\Command\Command
{

	use Nette\SmartObject;

	/** @var WebSockets\Server\Configuration */
	private WebSockets\Server\Configuration $configuration;

	/** @var WebSockets\Server\Handlers */
	private WebSockets\Server\Handlers $handlers;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/** @var SocketServerFactory\SocketServerFactory */
	private SocketServerFactory\SocketServerFactory $socketServerFactory;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	/**
	 * {@inheritDoc}
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName('fb:ws-server:start')
			->setDescription('Start WS server.');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output
	): int {
		$this->logger->info(
			'Starting HTTP server',
			[
				'source' => 'ws-server-plugin',
				'type'   => 'command',
			]
		);

		$server = $this->socketServerFactory->create($this->eventLoop, $this->configuration->getAddress(), $this->configuration->getPort());

		try {
			$server->on('connection', function (Socket\ConnectionInterface $connection): void {
				if ($connection->getLocalAddress() === null) {
					return;
				}

				$parsed = Utils\ArrayHash::from((array) parse_url($connection->getLocalAddress()));

				if ($parsed->offsetExists('port') && $parsed->offsetGet('port') === $this->configuration->getPort()) {
					$this->handlers->handleConnect($connection);
				}
			});

			$server->on('error', function (Throwable $ex): void {
				$this->logger->error('Could not establish connection', [
					'source'    => 'ws-server-plugin',
					'type'      => 'command',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
				]);
			});

			$this->logger->debug('Launching WebSockets Server', [
				'source' => 'ws-server-plugin',
				'type'   => 'command',
				'server' => [
					'address' => $this->configuration->getAddress(),
					'port'    => $this->configuration->getPort(),
				],
			]);

			$this->eventLoop->run();

		} catch (WebSockets\Exceptions\TerminateException $ex) {
			// Log error action reason
			$this->logger->error(
				'WS server was forced to close',
				[
					'source'    => 'ws-server-plugin',
					'type'      => 'command',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
					'cmd'       => $this->getName(),
				]
			);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping WS server',
				[
					'source'    => 'ws-server-plugin',
					'type'      => 'command',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
					'cmd'       => $this->getName(),
				]
			);

			$this->eventLoop->stop();
		}

		return 0;
	}

}
