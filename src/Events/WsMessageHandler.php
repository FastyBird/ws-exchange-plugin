<?php declare(strict_types = 1);

/**
 * WsMessageHandler.php
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

use IPub\WebSockets;
use Nette;
use Psr\Log;

/**
 * New client is connected to WS server
 *
 * @package         FastyBird:WsServerPlugin!
 * @subpackage      Events
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsMessageHandler
{

	use TSecurity;
	use Nette\SmartObject;

	/** @var Log\LoggerInterface */
	protected Log\LoggerInterface $logger;

	/** @var string[] */
	private array $wsKeys;

	/** @var string[] */
	private array $allowedOrigins;

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
	 * @param WebSockets\Entities\Clients\IClient $client
	 * @param WebSockets\Http\IRequest $httpRequest
	 *
	 * @return void
	 *
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	public function __invoke(
		WebSockets\Entities\Clients\IClient $client,
		WebSockets\Http\IRequest $httpRequest
	): void {
		$this->checkSecurity($client, $httpRequest, $this->wsKeys, $this->allowedOrigins);
	}

}
