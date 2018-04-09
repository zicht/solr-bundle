<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Cache;

use Psr\SimpleCache\CacheInterface;
use Zicht\Bundle\SolrBundle\Exception\InvalidCacheKeyException;

abstract class AbstractCache implements CacheInterface
{
    /**
     * @param string $key
     * @throws InvalidCacheKeyException
     */
    protected function validateKey($key)
    {
        if (!is_string($key) || false === (bool)@preg_match('/[a-z0-9_\.]{1,}/i', $key)) {
            throw new InvalidCacheKeyException($key);
        }
    }

    /**
     * @{inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        foreach ((array)$keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * @{inheritDoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    /**
     * @{inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }
}