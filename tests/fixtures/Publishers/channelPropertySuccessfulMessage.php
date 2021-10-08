<?php declare(strict_types = 1);

use FastyBird\ModulesMetadata;
use Nette\Utils;

return [
	'create' => [
		Utils\ArrayHash::from([
			'id'        => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
			'device'    => 'device-name',
			'channel'   => 'channel-name',
			'property'  => 'property-name',
			'name'      => null,
			'settable'  => true,
			'queryable' => true,
			'datatype'  => null,
			'unit'      => null,
			'format'    => null,
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_PROPERTY_ENTITY_CREATED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'update' => [
		Utils\ArrayHash::from([
			'id'        => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
			'device'    => 'device-name',
			'channel'   => 'channel-name',
			'property'  => 'property-name',
			'name'      => null,
			'settable'  => true,
			'queryable' => true,
			'datatype'  => null,
			'unit'      => null,
			'format'    => null,
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_PROPERTY_ENTITY_UPDATED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'delete' => [
		Utils\ArrayHash::from([
			'id'        => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
			'device'    => 'device-name',
			'channel'   => 'channel-name',
			'property'  => 'property-name',
			'name'      => null,
			'settable'  => true,
			'queryable' => true,
			'datatype'  => null,
			'unit'      => null,
			'format'    => null,
		]),
		ModulesMetadata\Types\RoutingKeyType::get(ModulesMetadata\Types\RoutingKeyType::ROUTE_CHANNELS_PROPERTY_ENTITY_DELETED),
		ModulesMetadata\Types\ModuleOriginType::get(ModulesMetadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
];
