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
use FastyBird\WsServerPlugin\Server;
use IPub\WebSockets;
use Nette;
use Psr\Log;
use React\EventLoop;
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

	/** @var Server\ServerFactory */
	private Server\ServerFactory $serverFactory;

	/** @var WebSockets\Server\Configuration */
	private WebSockets\Server\Configuration $configuration;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/** @var SocketServerFactory\SocketServerFactory */
	private SocketServerFactory\SocketServerFactory $socketServerFactory;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	public function __construct(
		WebSockets\Server\Configuration $configuration,
		Server\ServerFactory $serverFactory,
		SocketServerFactory\SocketServerFactory $socketServerFactory,
		EventLoop\LoopInterface $eventLoop,
		?Log\LoggerInterface $logger = null,
		?string $name = null
	) {
		$this->serverFactory = $serverFactory;

		$this->configuration = $configuration;
		$this->socketServerFactory = $socketServerFactory;

		$this->eventLoop = $eventLoop;

		$this->logger = $logger ?? new Log\NullLogger();

		parent::__construct($name);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName('fb:ws-server:start')
			->setDescription('WebSockets server service');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output
	): int {
		$this->logger->info(
			'Starting WS server',
			[
				'source' => 'ws-server-plugin',
				'type'   => 'command',
			]
		);

		try {
			$socketServer = $this->socketServerFactory->create(
				$this->eventLoop,
				$this->configuration->getAddress(),
				$this->configuration->getPort()
			);

			$this->serverFactory->create($socketServer);

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
