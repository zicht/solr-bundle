<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\SimpleCache;

/**
 * Class FilesystemCache
 *
 * @package Zicht\Bundle\SimpleCache
 */
class FilesystemCache extends AbstractCache
{
    /** @var string  */
    protected $base;

    /**
     * FileSystemCache constructor.
     *
     * @param string $base
     */
    public function __construct($base)
    {
        if (!is_dir($base) && false === @mkdir($base, 0777, true)) {
            if (null !== $message = error_get_last()) {
                $message = $message['message'];
            }

            throw new RuntimeException(sprintf('Failed to create directory "%s", %s', $base, $message));
        }
        $this->base = rtrim($base, DIRECTORY_SEPARATOR);
    }


    /**
     * @{inheritDoc}
     */
    public function get($key, $default = null)
    {
        if (($file = $this->getFile($key)) && is_file($file)) {
            $data = require $file;
            if (is_array($data) && 2 === \count($data)) {
                return $data[0];
            }
        }
        return $default;
    }

    /**
     * @{inheritDoc}
     */
    public function set($key, $value, $ttl = null)
    {
        return (bool)file_put_contents($this->getFile($key), $this->export([$value, $key]));
    }

    /**
     * @{inheritDoc}
     */
    public function delete($key)
    {
        if (($file = $this->getFile($key)) && is_file($file) && unlink($file)) {
                return true;
        }
        return false;
    }

    /**
     * @{inheritDoc}
     */
    public function clear()
    {
        $result = true;
        foreach(glob($this->base . '/*.php') as $file) {
            $result &= unlink($file);
        }
        return (bool)$result;
    }

    /**
     * @{inheritDoc}
     */
    public function has($key)
    {
        return is_file($this->getFile($key));
    }

    /**
     * @param string $key
     * @param null|string $suffix
     * @return string
     * @internal param string $type
     */
    private function getFile($key, $suffix = null)
    {
        $this->validateKey($key);
        return $this->base . '/' . sha1($key) . $suffix . '.php';
    }

    /**
     * @param mixed $item
     * @return string
     */
    private function export($item)
    {
        return sprintf('<?php return unserialize(%s);', var_export(serialize($item), true));
    }
}