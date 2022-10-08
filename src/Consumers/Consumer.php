<?php declare(strict_types = 1);

/**
 * Consumer.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Consumer
 * @since          0.19.0
 *
 * @date           08.07.22
 */

namespace FastyBird\WsServerPlugin\Consumers;

use FastyBird\Exchange\Consumer as ExchangeConsumer;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\WsServerPlugin\Publishers;
use Nette;

/**
 * Websockets exchange publisher
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Consumer
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Consumer implements ExchangeConsumer\Consumer
{

	use Nette\SmartObject;

	public function __construct(private readonly Publishers\Publisher $publisher)
	{
	}

	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		$this->publisher->publish($source, $routingKey, $entity);
	}

}
