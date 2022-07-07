<?php declare(strict_types = 1);

/**
 * ClientSubscriber.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Subscribers
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\WsServerPlugin\Subscribers;

use FastyBird\WsServerPlugin;
use FastyBird\WsServerPlugin\Events;
use IPub\WebSockets;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * WS client events subscriber
 *
 * @package         FastyBird:WsServerPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var Log\LoggerInterface */
	protected Log\LoggerInterface $logger;

	/** @var string[] */
	private array $wsKeys;

	/** @var string[] */
	private array $allowedOrigins;

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			Events\ClientConnectedEvent::class => 'clientConnected',
			Events\IncomingMessage::class      => 'incomingMessage',
		];
	}

	public function __construct(
		?Log\LoggerInterface $logger,
		?string $wsKeys = null,
		?string $allowedOrigins = null
	) {
		$this->wsKeys = $wsKeys !== null ? explode(',', $wsKeys) : [];
		$this->allowedOrigins = $allowedOrigins !== null ? explode(',', $allowedOrigins) : [];

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @param Events\ClientConnectedEvent $event
	 *
	 * @return void
	 *
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	public function clientConnected(
		Events\ClientConnectedEvent $event
	): void {
		$this->checkSecurity($event->getClient(), $event->getHttpRequest(), $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @param Events\IncomingMessage $event
	 *
	 * @return void
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	public function incomingMessage(
		Events\IncomingMessage $event
	): void {
		$this->checkSecurity($event->getClient(), $event->getHttpRequest(), $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @param WebSockets\Entities\Clients\IClient $client
	 * @param WebSockets\Http\IRequest $httpRequest
	 * @param string[] $allowedWsKeys
	 * @param string[] $allowedOrigins
	 *
	 * @return bool
	 *
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	public function checkSecurity(
		WebSockets\Entities\Clients\IClient $client,
		WebSockets\Http\IRequest $httpRequest,
		array $allowedWsKeys,
		array $allowedOrigins
	): bool {
		$wsKey = $httpRequest->getHeader(WsServerPlugin\Constants::WS_HEADER_WS_KEY);

		if (
			($wsKey === null && $allowedWsKeys !== [])
			|| (!in_array($wsKey, $allowedWsKeys, true) && $allowedWsKeys !== [])
		) {
			$this->closeSession($client);

			$this->logger->warning('Client used invalid WS key', [
				'source' => 'ws-server-plugin',
				'type'   => 'validate',
				'ws_key' => $wsKey,
			]);

			return false;
		}

		$origin = $httpRequest->getHeader(WsServerPlugin\Constants::WS_HEADER_ORIGIN);

		if (
			($origin === null && $allowedOrigins !== [])
			|| (!in_array($origin, $allowedOrigins, true) && $allowedOrigins !== [])
		) {
			$this->closeSession($client);

			$this->logger->warning('Client is connecting from not allowed origin', [
				'source' => 'ws-server-plugin',
				'type'   => 'validate',
				'origin' => $origin,
			]);

			return false;
		}

		$authToken = $httpRequest->getHeader(WsServerPlugin\Constants::WS_HEADER_AUTHORIZATION);

		if ($authToken === null) {
			$cookieToken = $httpRequest->getCookie('token');

			if ($cookieToken === null) {
				$this->logger->warning('Client access token is missing', [
					'source' => 'ws-server-plugin',
					'type'   => 'validate',
				]);

				$this->closeSession($client);

				return false;
			}
		}

		return true;
	}

	/**
	 * @param WebSockets\Entities\Clients\IClient $client
	 *
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	private function closeSession(WebSockets\Entities\Clients\IClient $client): void
	{
		$headers = [
			'X-Powered-By' => WebSockets\Server\Server::VERSION,
		];

		$response = new WebSockets\Application\Responses\ErrorResponse(401, $headers);

		$client->send($response);
		$client->close();
	}

}
