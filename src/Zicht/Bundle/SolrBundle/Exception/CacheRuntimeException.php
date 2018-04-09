<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Exception;

use Psr\SimpleCache\CacheException;

class CacheRuntimeException extends \RuntimeException implements SolrExceptionInterface, CacheException
{
}