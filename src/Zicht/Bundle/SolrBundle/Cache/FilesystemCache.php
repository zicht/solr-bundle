<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Cache;

use Zicht\Bundle\SolrBundle\Exception\CacheRuntimeException;

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
        if (!is_dir($base)) {
            if (false === @mkdir($base, 0777, true)) {
                throw new CacheRuntimeException(sprintf('Failed to create directory "%s", %s', $base, error_get_last()));
            }
        }
        $this->base = rtrim($base, DIRECTORY_SEPARATOR);
    }


    /**
     * @{inheritDoc}
     */
    public function get($key, $default = null)
    {
        if (is_file($file = $this->getFile($key))) {
            return require $file;
        }
        return $default;
    }

    /**
     * @{inheritDoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $status = true;
        $status &= (bool)file_put_contents($this->getFile($key), $this->export($value));
        $status &= (bool)file_put_contents($this->getFile($key, '.meta'), $this->export(['key' => $key]));
        return (bool)$status;
    }

    /**
     * @{inheritDoc}
     */
    public function delete($key)
    {
        if (($file = $this->getFile($key)) && is_file($file)) {
            if (unlink($file)) {
                return true;
            }
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
        return $result;
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