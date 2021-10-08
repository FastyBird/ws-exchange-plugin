<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\ExchangePlugin\Publisher as ExchangePluginPublisher;
use FastyBird\ModulesMetadata;
use FastyBird\WsServerPlugin\Publishers;
use Mockery;
use Nette\Utils;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class PublisherTest extends BaseTestCase
{

	/**
	 * @param Utils\ArrayHash $data
	 * @param ModulesMetadata\Types\RoutingKeyType $routingKey
	 * @param ModulesMetadata\Types\ModuleOriginType $origin
	 *
	 * @dataProvider ./../../../fixtures/Publishers/deviceSuccessfulMessage.php
	 */
	public function testPublishSuccessfulDeviceMessage(
		Utils\ArrayHash $data,
		ModulesMetadata\Types\RoutingKeyType $routingKey,
		ModulesMetadata\Types\ModuleOriginType $origin
	): void {
		$publisher = Mockery::mock(Publishers\Publisher::class);
		$publisher
			->shouldReceive('publish')
			->withArgs(
				function (
					ModulesMetadata\Types\ModuleOriginType $publisherOrigin,
					ModulesMetadata\Types\RoutingKeyType $publisherRoutingKey,
					Utils\ArrayHash $publisherData
				) use (
					$origin,
					$routingKey,
					$data
				): bool {
					Assert::same($origin, $publisherOrigin);
					Assert::same($routingKey, $publisherRoutingKey);
					Assert::same($data, $publisherData);

					return true;
				}
			)
			->andReturn(true)
			->times(1);

		$this->mockContainerService(Publishers\Publisher::class, $publisher);

		$publisherProxy = $this->container->getByType(ExchangePluginPublisher\Publisher::class);

		$publisherProxy->publish($origin, $routingKey, $data);
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param ModulesMetadata\Types\RoutingKeyType $routingKey
	 * @param ModulesMetadata\Types\ModuleOriginType $origin
	 *
	 * @dataProvider ./../../../fixtures/Publishers/devicePropertySuccessfulMessage.php
	 */
	public function testConsumeSuccessfulDevicePropertyMessage(
		Utils\ArrayHash $data,
		ModulesMetadata\Types\RoutingKeyType $routingKey,
		ModulesMetadata\Types\ModuleOriginType $origin
	): void {
		$publisher = Mockery::mock(Publishers\Publisher::class);
		$publisher
			->shouldReceive('publish')
			->withArgs(
				function (
					ModulesMetadata\Types\ModuleOriginType $publisherOrigin,
					ModulesMetadata\Types\RoutingKeyType $publisherRoutingKey,
					Utils\ArrayHash $publisherData
				) use (
					$origin,
					$routingKey,
					$data
				): bool {
					Assert::same($origin, $publisherOrigin);
					Assert::same($routingKey, $publisherRoutingKey);
					Assert::same($data, $publisherData);

					return true;
				}
			)
			->andReturn(true)
			->times(1);

		$this->mockContainerService(Publishers\Publisher::class, $publisher);

		$publisherProxy = $this->container->getByType(ExchangePluginPublisher\Publisher::class);

		$publisherProxy->publish($origin, $routingKey, $data);
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param ModulesMetadata\Types\RoutingKeyType $routingKey
	 * @param ModulesMetadata\Types\ModuleOriginType $origin
	 *
	 * @dataProvider ./../../../fixtures/Publishers/channelSuccessfulMessage.php
	 */
	public function testConsumeSuccessfulChannelMessage(
		Utils\ArrayHash $data,
		ModulesMetadata\Types\RoutingKeyType $routingKey,
		ModulesMetadata\Types\ModuleOriginType $origin
	): void {
		$publisher = Mockery::mock(Publishers\Publisher::class);
		$publisher
			->shouldReceive('publish')
			->withArgs(
				function (
					ModulesMetadata\Types\ModuleOriginType $publisherOrigin,
					ModulesMetadata\Types\RoutingKeyType $publisherRoutingKey,
					Utils\ArrayHash $publisherData
				) use (
					$origin,
					$routingKey,
					$data
				): bool {
					Assert::same($origin, $publisherOrigin);
					Assert::same($routingKey, $publisherRoutingKey);
					Assert::same($data, $publisherData);

					return true;
				}
			)
			->andReturn(true)
			->times(1);

		$this->mockContainerService(Publishers\Publisher::class, $publisher);

		$publisherProxy = $this->container->getByType(ExchangePluginPublisher\Publisher::class);

		$publisherProxy->publish($origin, $routingKey, $data);
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param ModulesMetadata\Types\RoutingKeyType $routingKey
	 * @param ModulesMetadata\Types\ModuleOriginType $origin
	 *
	 * @dataProvider ./../../../fixtures/Publishers/channelPropertySuccessfulMessage.php
	 */
	public function testConsumeSuccessfulChannelPropertyMessage(
		Utils\ArrayHash $data,
		ModulesMetadata\Types\RoutingKeyType $routingKey,
		ModulesMetadata\Types\ModuleOriginType $origin
	): void {
		$publisher = Mockery::mock(Publishers\Publisher::class);
		$publisher
			->shouldReceive('publish')
			->withArgs(
				function (
					ModulesMetadata\Types\ModuleOriginType $publisherOrigin,
					ModulesMetadata\Types\RoutingKeyType $publisherRoutingKey,
					Utils\ArrayHash $publisherData
				) use (
					$origin,
					$routingKey,
					$data
				): bool {
					Assert::same($origin, $publisherOrigin);
					Assert::same($routingKey, $publisherRoutingKey);
					Assert::same($data, $publisherData);

					return true;
				}
			)
			->andReturn(true)
			->times(1);

		$this->mockContainerService(Publishers\Publisher::class, $publisher);

		$publisherProxy = $this->container->getByType(ExchangePluginPublisher\Publisher::class);

		$publisherProxy->publish($origin, $routingKey, $data);
	}

}

$test_case = new PublisherTest();
$test_case->run();
