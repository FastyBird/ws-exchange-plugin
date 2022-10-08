<?php declare(strict_types = 1);

/**
 * WsServer.php
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

use FastyBird\WsServerPlugin\Server;
use IPub\WebSockets;
use Nette;
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
final class WsServer extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:ws-server:start';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly WebSockets\Server\Configuration $configuration,
		private readonly Server\Factory $serverFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
		string|null $name = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		parent::__construct($name);
	}

	protected function configure(): void
	{
		parent::configure();

		$this
			->setName(self::NAME)
			->setDescription('WebSockets server service');
	}

	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$this->logger->info(
			'Starting WS server',
			[
				'source' => 'ws-server-plugin',
				'type' => 'command',
			],
		);

		try {
			$socketServer = new Socket\SocketServer(
				$this->configuration->getAddress() . ':' . $this->configuration->getPort(),
				[],
				$this->eventLoop,
			);

			$this->serverFactory->create($socketServer);

			$this->eventLoop->run();

		} catch (WebSockets\Exceptions\TerminateException $ex) {
			// Log error action reason
			$this->logger->error(
				'WS server was forced to close',
				[
					'source' => 'ws-server-plugin',
					'type' => 'command',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping WS server',
				[
					'source' => 'ws-server-plugin',
					'type' => 'command',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();
		}

		return 0;
	}

}
