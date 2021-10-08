<?php declare(strict_types = 1);

/**
 * WsServerPluginExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           22.02.21
 */

namespace FastyBird\WsServerPlugin\DI;

use FastyBird\WsServerPlugin\Controllers;
use FastyBird\WsServerPlugin\Events;
use FastyBird\WsServerPlugin\Publishers;
use FastyBird\WsServerPlugin\Subscribers;
use IPub\WebSockets;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;

/**
 * MQTT client plugin
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsServerPluginExtension extends DI\CompilerExtension
{

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbWsServerPlugin'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new WsServerPluginExtension());
		};
	}

	/**
	 * {@inheritdoc}
	 */
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'keys' => Schema\Expect::string()->default(null),
			'origins' => Schema\Expect::string()->default(null),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		// Subscribers
		$builder->addDefinition($this->prefix('subscribers.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ApplicationSubscriber::class);

		// Events
		$builder->addDefinition($this->prefix('events.wsClientConnected'), new DI\Definitions\ServiceDefinition())
			->setType(Events\WsClientConnectedHandler::class)
			->setArgument('wsKeys', $configuration->keys)
			->setArgument('allowedOrigins', $configuration->origins);

		$builder->addDefinition($this->prefix('events.wsMessage'), new DI\Definitions\ServiceDefinition())
			->setType(Events\WsMessageHandler::class)
			->setArgument('wsKeys', $configuration->keys)
			->setArgument('allowedOrigins', $configuration->origins);

		// Controllers
		$builder->addDefinition($this->prefix('controllers.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ExchangeController::class)
			->addTag('nette.inject');

		// Publisher
		$builder->addDefinition($this->prefix('exchange.publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * WS SERVER EVENTS
		 */

		$socketWrapperServiceName = $builder->getByType(WebSockets\Server\Wrapper::class);

		if ($socketWrapperServiceName !== null) {
			/** @var DI\Definitions\ServiceDefinition $socketWrapperService */
			$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);

			$socketWrapperService
				->addSetup('$onClientConnected[]', ['@' . $this->prefix('events.wsClientConnected')])
				->addSetup('$onIncomingMessage[]', ['@' . $this->prefix('events.wsMessage')]);
		}
	}

}
