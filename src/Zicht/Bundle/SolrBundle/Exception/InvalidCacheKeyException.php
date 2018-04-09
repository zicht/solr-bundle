<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Exception;

use Psr\SimpleCache\InvalidArgumentException;

class InvalidCacheKeyException extends \InvalidArgumentException implements SolrExceptionInterface, InvalidArgumentException
{
    public function __construct($key, $code = 0, \Exception $previous = null)
    {
        if (is_scalar($key)) {
            $message = sprintf('Cache key "%s" has illegal characters, See https://www.php-fig.org/psr/psr-16/#12-definitions', $key);
        } else {
            $message = sprintf('Cache key must be string, "%s" given, See https://www.php-fig.org/psr/psr-16/#12-definitions', is_object($key) ? get_class($key) : gettype($key));

        }
        parent::__construct(sprintf($message, $code, $previous));
    }

}