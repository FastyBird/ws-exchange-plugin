<?php declare(strict_types = 1);

use FastyBird\ModulesMetadata;
use Nette\Utils;

return [
	'create' => [
		Utils\ArrayHash::from([
			'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device'  => 'device-name',
			'channel' => 'channel-name',
			'name'    => 'Channel custom name',
			'title'   => null,
			'comment' => null,
			'control' => [],
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_CREATED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'update' => [
		Utils\ArrayHash::from([
			'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device'  => 'device-name',
			'channel' => 'channel-name',
			'name'    => 'Channel custom name',
			'title'   => null,
			'comment' => null,
			'control' => [],
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_UPDATED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'delete' => [
		Utils\ArrayHash::from([
			'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device'  => 'device-name',
			'channel' => 'channel-name',
			'name'    => 'Channel custom name',
			'title'   => null,
			'comment' => null,
			'control' => [],
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_DELETED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
];
