<?php declare(strict_types = 1);

use FastyBird\Metadata;
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
		Metadata\Types\RoutingKeyType::get(Metadata\Types\RoutingKeyType::ROUTE_CHANNELS_PROPERTY_ENTITY_CREATED),
		Metadata\Types\ModuleOriginType::get(Metadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
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
		Metadata\Types\RoutingKeyType::get(Metadata\Types\RoutingKeyType::ROUTE_CHANNELS_PROPERTY_ENTITY_UPDATED),
		Metadata\Types\ModuleOriginType::get(Metadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
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
		Metadata\Types\RoutingKeyType::get(Metadata\Types\RoutingKeyType::ROUTE_CHANNELS_PROPERTY_ENTITY_DELETED),
		Metadata\Types\ModuleOriginType::get(Metadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
];
