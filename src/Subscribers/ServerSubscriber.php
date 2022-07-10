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

namespace FastyBird\WsServerPlugin\Subscribers;

use FastyBird\SocketServerFactory\Events as SocketServerFactoryEvents;
use FastyBird\WsServerPlugin\Server;
use Symfony\Component\EventDispatcher;

/**
 * Server startup subscriber
 *
 * @package         FastyBird:WsServerPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ServerSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var Server\ServerFactory */
	private Server\ServerFactory $serverFactory;

	public function __construct(
		Server\ServerFactory $serverFactory
	) {
		$this->serverFactory = $serverFactory;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			SocketServerFactoryEvents\InitializeEvent::class => 'initialize',
		];
	}

	/**
	 * @param SocketServerFactoryEvents\InitializeEvent $event
	 *
	 * @return void
	 */
	public function initialize(SocketServerFactoryEvents\InitializeEvent $event): void
	{
		$this->serverFactory->create($event->getServer());
	}

}
