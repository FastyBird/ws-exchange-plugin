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

use FastyBird\WsServerPlugin\Commands;
use FastyBird\WsServerPlugin\Controllers;
use FastyBird\WsServerPlugin\Events;
use FastyBird\WsServerPlugin\Exceptions;
use FastyBird\WsServerPlugin\Publishers;
use FastyBird\WsServerPlugin\Subscribers;
use IPub\WebSockets;
use Nette;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Psr\EventDispatcher;
use stdClass;

/**
 * WS server plugin
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
			'access' => Schema\Expect::structure([
				'keys'    => Schema\Expect::string()->default(null),
				'origins' => Schema\Expect::string()->default(null),
			]),
			'server' => Schema\Expect::structure([
				'command' => Schema\Expect::bool()->default(true),
			]),
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
		if (!$configuration->server->command) {
			$builder->addDefinition($this->prefix('subscribers.initialize'), new DI\Definitions\ServiceDefinition())
				->setType(Subscribers\ApplicationSubscriber::class);
		}

		$builder->addDefinition($this->prefix('subscribers.server'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ServerSubscriber::class)
			->setArgument('wsKeys', $configuration->access->keys)
			->setArgument('allowedOrigins', $configuration->access->origins);

		// Controllers
		$builder->addDefinition($this->prefix('controllers.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ExchangeController::class)
			->addTag('nette.inject');

		// Publisher
		$builder->addDefinition($this->prefix('exchange.publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class);

		// Commands
		$builder->addDefinition($this->prefix('command.server'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\WsServerCommand::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * Events bridges
		 */

		if ($builder->getByType(EventDispatcher\EventDispatcherInterface::class) === null) {
			throw new Exceptions\LogicException(sprintf('Service of type "%s" is needed. Please register it.', EventDispatcher\EventDispatcherInterface::class));
		}

		$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

		$socketWrapperServiceName = $builder->getByType(WebSockets\Server\Wrapper::class);
		assert(is_string($socketWrapperServiceName));

		$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);
		assert($socketWrapperService instanceof DI\Definitions\ServiceDefinition);

		$socketWrapperService->addSetup('?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			new PhpGenerator\Literal(Events\ClientConnectedEvent::class),
		]);

		$socketWrapperService->addSetup('?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			new PhpGenerator\Literal(Events\IncomingMessage::class),
		]);
	}

}
