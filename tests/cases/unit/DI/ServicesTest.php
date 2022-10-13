<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use FastyBird\WsExchangePlugin\Controllers;
use FastyBird\WsExchangePlugin\Subscribers;
use Tests\Cases\Unit\BaseTestCase;

final class ServicesTest extends BaseTestCase
{

	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		$this->assertNotNull($container->getByType(Subscribers\Client::class));

		$this->assertNotNull($container->getByType(Controllers\Exchange::class));
	}

}
