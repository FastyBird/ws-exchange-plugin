<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Metadata;
use FastyBird\WsServerPlugin\Publishers;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
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
	 * @param Metadata\Types\RoutingKeyType $routingKey
	 * @param Metadata\Types\ModuleOriginType $origin
	 *
	 * @dataProvider ./../../../fixtures/Publishers/deviceSuccessfulMessage.php
	 */
	public function testPublishSuccessfulDeviceMessage(
		Utils\ArrayHash $data,
		Metadata\Types\RoutingKeyType $routingKey,
		Metadata\Types\ModuleOriginType $origin
	): void {
		$linkGenerator = Mockery::mock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->shouldReceive('link')
			->with('Exchange:')
			->andReturn('topic-link')
			->times(1);

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = Mockery::mock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->shouldReceive('broadcast')
			->withArgs(
				function ($message) use (
					$origin,
					$routingKey,
					$data
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin'      => $origin->getValue(),
						'data'        => $this->dataToArray($data),
					];

					Assert::same(Utils\Json::encode($mockedData), $message);

					return true;
				}
			)
			->times(1);

		$topicsStorage = Mockery::mock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->shouldReceive('hasTopic')
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('getTopic')
			->andReturn($topicMock)
			->times(1);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($origin, $routingKey, $data);
	}

	/**
	 * @param Utils\ArrayHash $data
	 *
	 * @return mixed[]
	 */
	private function dataToArray(Utils\ArrayHash $data): array
	{
		$transformed = (array) $data;

		foreach ($transformed as $key => $value) {
			if ($value instanceof Utils\ArrayHash) {
				$transformed[$key] = $this->dataToArray($value);
			}
		}

		return $transformed;
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param Metadata\Types\RoutingKeyType $routingKey
	 * @param Metadata\Types\ModuleOriginType $origin
	 *
	 * @dataProvider ./../../../fixtures/Publishers/devicePropertySuccessfulMessage.php
	 */
	public function testPublishSuccessfulDevicePropertyMessage(
		Utils\ArrayHash $data,
		Metadata\Types\RoutingKeyType $routingKey,
		Metadata\Types\ModuleOriginType $origin
	): void {
		$linkGenerator = Mockery::mock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->shouldReceive('link')
			->with('Exchange:')
			->andReturn('topic-link')
			->times(1);

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = Mockery::mock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->shouldReceive('broadcast')
			->withArgs(
				function ($message) use (
					$origin,
					$routingKey,
					$data
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin'      => $origin->getValue(),
						'data'        => $this->dataToArray($data),
					];

					Assert::same(Utils\Json::encode($mockedData), $message);

					return true;
				}
			)
			->times(1);

		$topicsStorage = Mockery::mock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->shouldReceive('hasTopic')
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('getTopic')
			->andReturn($topicMock)
			->times(1);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($origin, $routingKey, $data);
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param Metadata\Types\RoutingKeyType $routingKey
	 * @param Metadata\Types\ModuleOriginType $origin
	 *
	 * @dataProvider ./../../../fixtures/Publishers/channelSuccessfulMessage.php
	 */
	public function testPublishSuccessfulChannelMessage(
		Utils\ArrayHash $data,
		Metadata\Types\RoutingKeyType $routingKey,
		Metadata\Types\ModuleOriginType $origin
	): void {
		$linkGenerator = Mockery::mock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->shouldReceive('link')
			->with('Exchange:')
			->andReturn('topic-link')
			->times(1);

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = Mockery::mock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->shouldReceive('broadcast')
			->withArgs(
				function ($message) use (
					$origin,
					$routingKey,
					$data
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin'      => $origin->getValue(),
						'data'        => $this->dataToArray($data),
					];

					Assert::same(Utils\Json::encode($mockedData), $message);

					return true;
				}
			)
			->times(1);

		$topicsStorage = Mockery::mock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->shouldReceive('hasTopic')
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('getTopic')
			->andReturn($topicMock)
			->times(1);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($origin, $routingKey, $data);
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param Metadata\Types\RoutingKeyType $routingKey
	 * @param Metadata\Types\ModuleOriginType $origin
	 *
	 * @dataProvider ./../../../fixtures/Publishers/channelPropertySuccessfulMessage.php
	 */
	public function testPublishSuccessfulChannelPropertyMessage(
		Utils\ArrayHash $data,
		Metadata\Types\RoutingKeyType $routingKey,
		Metadata\Types\ModuleOriginType $origin
	): void {
		$linkGenerator = Mockery::mock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->shouldReceive('link')
			->with('Exchange:')
			->andReturn('topic-link')
			->times(1);

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = Mockery::mock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->shouldReceive('broadcast')
			->withArgs(
				function ($message) use (
					$origin,
					$routingKey,
					$data
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin'      => $origin->getValue(),
						'data'        => $this->dataToArray($data),
					];

					Assert::same(Utils\Json::encode($mockedData), $message);

					return true;
				}
			)
			->times(1);

		$topicsStorage = Mockery::mock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->shouldReceive('hasTopic')
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('getTopic')
			->andReturn($topicMock)
			->times(1);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($origin, $routingKey, $data);
	}

}

$test_case = new PublisherTest();
$test_case->run();
