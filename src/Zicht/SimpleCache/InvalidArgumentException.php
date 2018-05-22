<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\SimpleCache;

use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInterface;

class InvalidArgumentException extends \InvalidArgumentException implements SimpleCacheInterface
{
}
