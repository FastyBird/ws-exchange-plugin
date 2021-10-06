<?php declare(strict_types = 1);

use FastyBird\ModulesMetadata;

return [
	'create' => [
		[
			'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device'  => 'device-name',
			'channel' => 'channel-name',
			'name'    => 'Channel custom name',
			'title'   => null,
			'comment' => null,
			'control' => [],
		],
		[
			'routing_key' => ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_CREATED,
			'origin'      => ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES,
			'data'        => [
				'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
				'device'  => 'device-name',
				'channel' => 'channel-name',
				'name'    => 'Channel custom name',
				'title'   => null,
				'comment' => null,
				'control' => [],
			],
		],
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_CREATED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'update' => [
		[
			'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device'  => 'device-name',
			'channel' => 'channel-name',
			'name'    => 'Channel custom name',
			'title'   => null,
			'comment' => null,
			'control' => [],
		],
		[
			'routing_key' => ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_UPDATED,
			'origin'      => ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES,
			'data'        => [
				'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
				'device'  => 'device-name',
				'channel' => 'channel-name',
				'name'    => 'Channel custom name',
				'title'   => null,
				'comment' => null,
				'control' => [],
			],
		],
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_UPDATED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'delete' => [
		[
			'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device'  => 'device-name',
			'channel' => 'channel-name',
			'name'    => 'Channel custom name',
			'title'   => null,
			'comment' => null,
			'control' => [],
		],
		[
			'routing_key' => ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_DELETED,
			'origin'      => ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES,
			'data'        => [
				'id'      => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
				'device'  => 'device-name',
				'channel' => 'channel-name',
				'name'    => 'Channel custom name',
				'title'   => null,
				'comment' => null,
				'control' => [],
			],
		],
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_ENTITY_DELETED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
];
