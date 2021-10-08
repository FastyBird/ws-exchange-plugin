<?php declare(strict_types = 1);

use FastyBird\ModulesMetadata;
use Nette\Utils;

return [
	'create' => [
		Utils\ArrayHash::from([
			'id'         => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
			'device'     => 'device-name',
			'identifier' => 'device-name',
			'name'       => 'Device name',
			'title'      => null,
			'comment'    => null,
			'state'      => 'ready',
			'enabled'    => true,
			'control'    => ['reset', 'reboot'],
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_DEVICES_ENTITY_CREATED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'update' => [
		Utils\ArrayHash::from([
			'id'         => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
			'device'     => 'device-name',
			'identifier' => 'device-name',
			'name'       => 'Device name',
			'title'      => null,
			'comment'    => null,
			'state'      => 'ready',
			'enabled'    => true,
			'control'    => ['reset', 'reboot'],
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_DEVICES_ENTITY_UPDATED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'delete' => [
		Utils\ArrayHash::from([
			'id'         => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
			'device'     => 'device-name',
			'identifier' => 'device-name',
			'name'       => 'Device name',
			'title'      => null,
			'comment'    => null,
			'state'      => 'ready',
			'enabled'    => true,
			'control'    => ['reset', 'reboot'],
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_DEVICES_ENTITY_DELETED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
];
