<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use FastyBird\WsExchangePlugin\Controllers;
use FastyBird\WsExchangePlugin\Subscribers;
use Nette;
use Tests\Cases\Unit\BaseTestCase;

final class ServicesTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Subscribers\Client::class, false));

		self::assertNotNull($container->getByType(Controllers\Exchange::class, false));
	}

}
