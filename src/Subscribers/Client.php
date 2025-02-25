<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           15.01.22
 */

namespace FastyBird\Plugin\WsServer\Subscribers;

use Doctrine\DBAL;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WsServer;
use FastyBird\Plugin\WsServer\Events;
use IPub\WebSockets;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use function explode;
use function in_array;

/**
 * WS client events subscriber
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Client implements EventDispatcher\EventSubscriberInterface
{

	/** @var array<string> */
	private array $wsKeys;

	/** @var array<string> */
	private array $allowedOrigins;

	public function __construct(
		private readonly ToolsHelpers\Database $database,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
		string|null $wsKeys = null,
		string|null $allowedOrigins = null,
	)
	{
		$this->wsKeys = $wsKeys !== null ? explode(',', $wsKeys) : [];
		$this->allowedOrigins = $allowedOrigins !== null ? explode(',', $allowedOrigins) : [];
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\ClientConnected::class => 'clientConnected',
			Events\IncomingMessage::class => 'incomingMessage',
		];
	}

	/**
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	public function clientConnected(Events\ClientConnected $event): void
	{
		$this->checkSecurity($event->getClient(), $event->getHttpRequest(), $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws ToolsExceptions\InvalidState
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 * @throws WebSockets\Exceptions\TerminateException
	 */
	public function incomingMessage(Events\IncomingMessage $event): void
	{
		// Check if ping to DB is possible...
		if (!$this->database->ping()) {
			// ...if not, try to reconnect
			$this->database->reconnect();

			// ...and ping again
			if (!$this->database->ping()) {
				throw new WebSockets\Exceptions\TerminateException(
					'Connection to database could not be re-established',
				);
			}
		}

		$this->checkSecurity($event->getClient(), $event->getHttpRequest(), $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @param array<string> $allowedWsKeys
	 * @param array<string> $allowedOrigins
	 *
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	public function checkSecurity(
		WebSockets\Entities\Clients\IClient $client,
		WebSockets\Http\IRequest $httpRequest,
		array $allowedWsKeys,
		array $allowedOrigins,
	): bool
	{
		$wsKey = $httpRequest->getHeader(WsServer\Constants::WS_HEADER_WS_KEY);

		if (
			($wsKey === null && $allowedWsKeys !== [])
			|| (!in_array($wsKey, $allowedWsKeys, true) && $allowedWsKeys !== [])
		) {
			$this->closeSession($client);

			$this->logger->warning('Client used invalid WS key', [
				'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
				'type' => 'subscriber',
				'ws_key' => $wsKey,
			]);

			return false;
		}

		$origin = $httpRequest->getHeader(WsServer\Constants::WS_HEADER_ORIGIN);

		if (
			($origin === null && $allowedOrigins !== [])
			|| (!in_array($origin, $allowedOrigins, true) && $allowedOrigins !== [])
		) {
			$this->closeSession($client);

			$this->logger->warning('Client is connecting from not allowed origin', [
				'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
				'type' => 'subscriber',
				'origin' => $origin,
			]);

			return false;
		}

		$authToken = $httpRequest->getHeader(WsServer\Constants::WS_HEADER_AUTHORIZATION);

		if ($authToken === null) {
			$cookieToken = $httpRequest->getCookie('token');

			if ($cookieToken === null) {
				$this->logger->warning('Client access token is missing', [
					'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
					'type' => 'subscriber',
				]);

				$this->closeSession($client);

				return false;
			}
		}

		return true;
	}

	/**
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
