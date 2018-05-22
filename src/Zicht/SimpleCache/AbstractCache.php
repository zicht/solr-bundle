<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\SimpleCache;

use Psr\SimpleCache\CacheInterface;

/**
 * Class AbstractCache
 *
 * @package Zicht\Bundle\SolrBundle\SimpleCache
 */
abstract class AbstractCache implements CacheInterface
{
    /**
     * @param string $key
     * @throws InvalidKeyException
     */
    protected function validateKey($key)
    {
        if (!is_scalar($key) || false === (bool)@preg_match('/[a-z0-9_\.]{1,}/i', $key)) {
            throw new InvalidKeyException($key);
        }
    }

    /**
     * @param mixed $v
     */
    protected function validateMultiArg($v)
    {
        if (false === (is_array($v) || $v instanceof \Traversable)) {
            throw new InvalidArgumentException(sprintf('First argument for a multi function should be an "array" or a "\Traversable", "%s" given', gettype($v)));
        }
    }

    /**
     * @{inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $this->validateMultiArg($keys);
        // If returned directly test will fail because we expect
        // an exception on invalid keys before iteration starts.
        return $this->getGenerator($keys, $default);
    }

    /**
     * @param mixed $keys
     * @param mixed $default
     * @return \Generator
     */
    private function getGenerator($keys, $default)
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * @{inheritDoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->validateMultiArg($values);
        $state = true;
        foreach ($values as $key => $value) {
            $state &= $this->set($key, $value, $ttl);
        }
        return (bool)$state;
    }

    /**
     * @{inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        $this->validateMultiArg($keys);
        $state = true;
        foreach ($keys as $key) {
            $state &= $this->delete($key);
        }
        return (bool)$state;
    }
}