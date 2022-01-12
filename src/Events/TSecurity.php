<?php declare(strict_types = 1);

/**
 * TSecurity.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 * @since          0.1.0
 *
 * @date           05.05.20
 */

namespace FastyBird\WsServerPlugin\Events;

use FastyBird\WsServerPlugin;
use IPub\WebSockets;
use Psr\Log;

/**
 * Security trait
 *
 * @package         FastyBird:WsServerPlugin!
 * @subpackage      Events
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Log\LoggerInterface $logger
 */
trait TSecurity
{

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
				'source' => 'ws-server-plugin-security',
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
				'source' => 'ws-server-plugin-security',
				'type'   => 'validate',
				'origin' => $origin,
			]);

			return false;
		}

		$authToken = $httpRequest->getHeader(WsServerPlugin\Constants::WS_HEADER_AUTHORIZATION);

		if ($authToken === null) {
			$this->logger->warning('Client access token is missing', [
				'source' => 'ws-server-plugin-security',
				'type'   => 'validate',
			]);

			$this->closeSession($client);

			return false;
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
