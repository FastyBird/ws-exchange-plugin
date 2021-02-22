<?php declare(strict_types = 1);

/**
 * ApplicationSubscriber.php
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

namespace FastyBird\WsServerPlugin\Subscribers;

use FastyBird\ApplicationEvents\Events as ApplicationEventsEvents;
use IPub\WebSockets;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Socket;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Server startup subscriber
 *
 * @package         FastyBird:WsServerPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApplicationSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var Socket\Server */
	private Socket\Server $socketServer;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $loop;

	/** @var WebSockets\Server\Handlers */
	private WebSockets\Server\Handlers $handlers;

	/** @var WebSockets\Server\Configuration */
	private WebSockets\Server\Configuration $configuration;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/**
	 * @return string[]
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEventsEvents\StartupEvent::class  => 'initialize',
		];
	}

	public function __construct(
		EventLoop\LoopInterface $loop,
		Socket\Server $socketServer,
		WebSockets\Server\Handlers $handlers,
		WebSockets\Server\Configuration $configuration,
		?Log\LoggerInterface $logger = null
	) {
		$this->loop = $loop;
		$this->socketServer = $socketServer;
		$this->handlers = $handlers;
		$this->configuration = $configuration;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @return void
	 */
	public function initialize(): void
	{
		$client = $this->configuration->getAddress() . ':' . $this->configuration->getPort();
		$socket = new Socket\Server($client, $this->loop);

		$socket->on('connection', function (Socket\ConnectionInterface $connection): void {
			$this->handlers->handleConnect($connection);
		});

		$socket->on('error', function (Throwable $ex): void {
			$this->logger->error('Could not establish connection: ' . $ex->getMessage());
		});

		$this->logger->debug(sprintf('Launching WebSockets WS Server on: %s:%s', $this->configuration->getAddress(), $this->configuration->getPort()));

		$this->socketServer->on('connection', function (Socket\ConnectionInterface $connection): void {
			if ($connection->getLocalAddress() === null) {
				return;
			}

			$parsed = Utils\ArrayHash::from((array) parse_url($connection->getLocalAddress()));

			if ($parsed->offsetExists('port') && $parsed->offsetGet('port') === $this->configuration->getPort()) {
				$this->handlers->handleConnect($connection);
			}
		});
	}

}
