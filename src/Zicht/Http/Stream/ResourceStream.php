<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Stream;

use Psr\Http\Message\StreamInterface;
use Zicht\Http\Exception\RuntimeException;
use Zicht\Http\Exception\InvalidArgumentException;

class ResourceStream implements StreamInterface
{
    /** @var resource  */
    private $resource;
    /** @var array  */
    private $meta;
    /** @var array  */
    private $stats;

    /**
     * ResourceStream constructor.
     *
     * @param $resource
     */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException(sprintf('Expected a resource got "%s".', gettype($resource)));
        }

        $this->resource = $resource;
    }

    /**
     * @{inheritDoc}
     */
    public function __destruct()
    {
        $this->close();
    }


    /**
     * @param string $default
     * @throws RuntimeException
     */
    private function error($default)
    {
        if (null !== $message = error_get_last()) {
            $default = $message['error'];
        }

        throw new RuntimeException($default);
    }

    /**
     * @{inheritDoc}
     */
    public function __toString()
    {
        try {
            $offset = $this->tell();
            $this->seek(0);
            $data = stream_get_contents($this->resource);
            $this->seek($offset);
            return $data;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @{inheritDoc}
     */
    public function close()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
            $this->detach();
        }
    }

    /**
     * @{inheritDoc}
     */
    public function detach()
    {
        if (null === $this->resource) {
            return null;
        }

        $resource = $this->resource;
        $this->resource = null;
        $this->clear();
        return $resource;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (null === $this->resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (null === $this->stats) {
            $this->stats = fstat($this->resource);
        }

        if (isset($this->stats['size'])) {
            return $this->stats['size'];
        }

        return null;
    }

    /**
     * @{inheritDoc}
     */
    public function tell()
    {
        if (null === $this->resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (false === $index = ftell($this->resource)) {
            $this->error('failed to get current position of pointer in resource.');
        }

        return $index;
    }

    /**
     * @{inheritDoc}
     */
    public function eof()
    {
        if (null === $this->resource) {
            throw new RuntimeException('Stream is detached.');
        }

        return feof($this->resource);
    }

    /**
     * @{inheritDoc}
     */
    public function isSeekable()
    {
        return (bool)$this->getMetadata('seekable');
    }

    /**
     * @{inheritDoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (null === $this->resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (-1 === $ret = fseek($this->resource, $offset, $whence)) {
            $this->error('failed to seek a position in the stream.');
        }

        $this->clear();

        return $ret;
    }

    /**
     * @{inheritDoc}
     */
    public function rewind()
    {
        if (false === $this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        return $this->seek(0);
    }

    /**
     * @{inheritDoc}
     */
    public function isWritable()
    {
        return (bool)preg_match('/^(?:r\+|w|a|x|c)/', $this->getMetadata('mode'));
    }

    /**
     * @{inheritDoc}
     */
    public function write($string)
    {
        if (false === $this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        $this->clear();

        return fwrite($this->resource, $string);

    }

    /**
     * @{inheritDoc}
     */
    public function isReadable()
    {
        return (bool)preg_match('/^(?:r|(?:w|a|x|c)\+)/', $this->getMetadata('mode'));
    }

    /**
     * @{inheritDoc}
     */
    public function read($length)
    {
        if (false === $this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        $this->clear();

        return fread($this->resource, $length);
    }

    /**
     * @{inheritDoc}
     */
    public function getContents()
    {

        if (null === $this->resource) {
            throw new RuntimeException('Stream is detached.');
        }

        $this->clear();

        return stream_get_contents($this->resource);
    }

    private function clear()
    {
        $this->meta = $this->stats = null;
    }

    /**
     * @{inheritDoc}
     */
    public function getMetadata($key = null)
    {
        if (null === $this->resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (null === $this->meta) {
            $this->meta = stream_get_meta_data($this->resource);
        }

        if (empty($key)) {
            return $this->meta;
        } else {
            return (isset($this->meta[$key])) ? $this->meta[$key] : null;
        }
    }
}
