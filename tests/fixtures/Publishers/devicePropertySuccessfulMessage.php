<?php declare(strict_types = 1);

use FastyBird\Metadata;
use Nette\Utils;

return [
	'create' => [
		Utils\ArrayHash::from([
			'id'        => 'b41efd22-42e0-4e30-aab4-de471741cd30',
			'device'    => 'device-name',
			'property'  => 'property-name',
			'name'      => null,
			'settable'  => true,
			'queryable' => true,
			'datatype'  => null,
			'unit'      => null,
			'format'    => null,
		]),
		Metadata\Types\RoutingKeyType::get(Metadata\Types\RoutingKeyType::ROUTE_DEVICES_PROPERTY_ENTITY_CREATED),
		Metadata\Types\ModuleOriginType::get(Metadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'update' => [
		Utils\ArrayHash::from([
			'id'        => 'b41efd22-42e0-4e30-aab4-de471741cd30',
			'device'    => 'device-name',
			'property'  => 'property-name',
			'name'      => null,
			'settable'  => true,
			'queryable' => true,
			'datatype'  => null,
			'unit'      => null,
			'format'    => null,
		]),
		Metadata\Types\RoutingKeyType::get(Metadata\Types\RoutingKeyType::ROUTE_DEVICES_PROPERTY_ENTITY_UPDATED),
		Metadata\Types\ModuleOriginType::get(Metadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
	'delete' => [
		Utils\ArrayHash::from([
			'id'        => 'b41efd22-42e0-4e30-aab4-de471741cd30',
			'device'    => 'device-name',
			'property'  => 'property-name',
			'name'      => null,
			'settable'  => true,
			'queryable' => true,
			'datatype'  => null,
			'unit'      => null,
			'format'    => null,
		]),
		Metadata\Types\RoutingKeyType::get(Metadata\Types\RoutingKeyType::ROUTE_DEVICES_PROPERTY_ENTITY_DELETED),
		Metadata\Types\ModuleOriginType::get(Metadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES),
	],
];
