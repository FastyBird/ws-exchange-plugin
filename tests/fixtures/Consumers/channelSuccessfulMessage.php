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
			'routing_key' => ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_CREATED_ENTITY_ROUTING_KEY,
			'origin'      => ModulesMetadata\Constants::MODULE_DEVICES_ORIGIN,
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
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_CREATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MODULE_DEVICES_ORIGIN,
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
			'routing_key' => ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_UPDATED_ENTITY_ROUTING_KEY,
			'origin'      => ModulesMetadata\Constants::MODULE_DEVICES_ORIGIN,
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
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_UPDATED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MODULE_DEVICES_ORIGIN,
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
			'routing_key' => ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_DELETED_ENTITY_ROUTING_KEY,
			'origin'      => ModulesMetadata\Constants::MODULE_DEVICES_ORIGIN,
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
		ModulesMetadata\Constants::MESSAGE_BUS_CHANNELS_DELETED_ENTITY_ROUTING_KEY,
		ModulesMetadata\Constants::MODULE_DEVICES_ORIGIN,
	],
];
