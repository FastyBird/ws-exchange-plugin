<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           09.03.20
 */

namespace FastyBird\WsServerPlugin;

/**
 * Service constants
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * Service headers
	 */

	public const WS_HEADER_AUTHORIZATION = 'authorization';

	public const WS_HEADER_WS_KEY = 'x-ws-key';

	public const WS_HEADER_ORIGIN = 'origin';

}
