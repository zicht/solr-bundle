<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\SimpleCache;

use Psr\SimpleCache\CacheException;

/**
 * Class RuntimeException
 * @package Zicht\SimpleCache
 */
class RuntimeException extends \RuntimeException implements CacheException
{
}
