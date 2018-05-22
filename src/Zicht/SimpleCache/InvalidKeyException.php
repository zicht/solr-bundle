<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\SimpleCache;

/**
 * Class InvalidKeyException
 *
 * @package Zicht\Bundle\SimpleCache
 */
class InvalidKeyException extends InvalidArgumentException
{
    /** @var string */
    const DOC_URL = 'https://www.php-fig.org/psr/psr-16/#12-definitions';

    /**
     * InvalidCacheKeyException constructor.
     *
     * @param string $key
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($key, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf($this->createMessage($key), $code, $previous));
    }

    /**
     * @param mixed $key
     * @return string
     */
    private function createMessage($key)
    {
        if (is_scalar($key)) {
            return sprintf('Cache key "%s" has illegal characters, See %s', $key, self::DOC_URL);
        } else {
            return sprintf('Cache key must be string, "%s" given, See %s', is_object($key) ? get_class($key) : gettype($key), self::DOC_URL);
        }
    }
}
