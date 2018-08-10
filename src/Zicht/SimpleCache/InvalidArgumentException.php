<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\SimpleCache;

use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInterface;

/**
 * Class InvalidArgumentException
 * @package Zicht\SimpleCache
 */
class InvalidArgumentException extends \InvalidArgumentException implements SimpleCacheInterface
{
}
