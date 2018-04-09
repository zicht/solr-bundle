<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Cache;

use Psr\SimpleCache\CacheInterface;
use Zicht\Bundle\SolrBundle\Exception\CacheRuntimeException;

class FileSystemCache implements CacheInterface
{
    use KeyTrait;

    /** @var string  */
    protected $base;

    /**
     * FileSystemCache constructor.
     *
     * @param string $base
     */
    public function __construct($base)
    {
        if (!is_dir($base)) {
            if (false === @mkdir($base, 0777, true)) {
                throw new CacheRuntimeException(sprintf('Failed to create directory "%s", %s', $base, error_get_last()));
            }
        }
        $this->base = ltrim($base, DIRECTORY_SEPARATOR);
    }


    /**
     * @{inheritDoc}
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);

        if (is_file($file = $this->getFilePath($key))) {
            if (false === $contents = @file_get_contents($file)) {
                throw new CacheRuntimeException(sprintf('Failed to read from file "%s", %s', $file, error_get_last());
            }

            if (false === $data =

            list(,$value) = unserialize(file_get_contents($file));
        }

        return $default;
    }

    /**
     * @{inheritDoc}
     */
    public function set($key, $value, $ttl = null)
    {
        // TODO: Implement set() method.
    }

    /**
     * @{inheritDoc}
     */
    public function delete($key)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @{inheritDoc}
     */
    public function clear()
    {
        // TODO: Implement clear() method.
    }

    /**
     * @{inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        // TODO: Implement getMultiple() method.
    }

    /**
     * @{inheritDoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        // TODO: Implement setMultiple() method.
    }

    /**
     * @{inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        // TODO: Implement deleteMultiple() method.
    }

    /**
     * @{inheritDoc}
     */
    public function has($key)
    {
        return is_file($this->getFilePath($key));
    }

    /**
     * @param string $key
     * @return string
     */
    private function getFilePath($key)
    {
        return $this->base . '/' . sha1($key);
    }

    private function encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}