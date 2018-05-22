<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\SimpleCache;

/**
 * Class ArrayCache
 *
 * @package Zicht\Bundle\SimpleCache
 */
class ArrayCache extends AbstractCache
{
    /** @var array  */
    private $storage = [];

    /**
     * @{inheritDoc}
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);
        return isset($this->storage[$key]) ? $this->storage[$key] : $default;
    }

    /**
     * @{inheritDoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->validateKey($key);
        $this->storage[$key] = $value;
        return true;
    }

    /**
     * @{inheritDoc}
     */
    public function delete($key)
    {
        if ($this->has($key)) {
            unset($this->storage[$key]);
            return true;
        }
        return false;
    }

    /**
     * @{inheritDoc}
     */
    public function clear()
    {
        $this->storage = [];
        return true;
    }

    /**
     * @{inheritDoc}
     */
    public function has($key)
    {
        $this->validateKey($key);
        return isset($this->storage[$key]);
    }
}