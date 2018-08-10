<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * Class WrappedDebugStream
 * @package Zicht\Http\Stream
 */
class WrappedDebugStream implements StreamInterface
{
    /** @var StreamInterface  */
    private $stream;
    /** @var StreamInterface  */
    private $logger;

    /**
     * WrappedDebugStream constructor.
     *
     * @param StreamInterface $stream
     * @param StreamInterface $logger
     */
    public function __construct(StreamInterface $stream, StreamInterface $logger)
    {
        $this->stream = $stream;
        $this->logger = $logger;
    }

    /**
     * @{inheritDoc}
     */
    public function __toString()
    {
        return (string)$this->stream;
    }

    /**
     * @{inheritDoc}
     */
    public function close()
    {
        $this->stream->close();
    }

    /**
     * @{inheritDoc}
     */
    public function detach()
    {
        return $this->stream->detach();
    }

    /**
     * @{inheritDoc}
     */
    public function getSize()
    {
        return $this->stream->getSize();
    }

    /**
     * @{inheritDoc}
     */
    public function tell()
    {
        return $this->stream->tell();
    }

    /**
     * @{inheritDoc}
     */
    public function eof()
    {
        return $this->stream->eof();
    }

    /**
     * @{inheritDoc}
     */
    public function isSeekable()
    {
        return $this->stream->isSeekable();
    }

    /**
     * @{inheritDoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->stream->seek($offset, $whence);
    }

    /**
     * @{inheritDoc}
     */
    public function rewind()
    {
        return $this->stream->rewind();
    }

    /**
     * @{inheritDoc}
     */
    public function isWritable()
    {
        return $this->stream->isWritable();
    }

    /**
     * @{inheritDoc}
     */
    public function write($string)
    {
        $this->logger->write($this->decorate($string));
        return $this->stream->write($string);
    }

    /**
     * @{inheritDoc}
     */
    public function isReadable()
    {
        return $this->stream->isSeekable();
    }

    /**
     * @{inheritDoc}
     */
    public function read($length)
    {
        $data = $this->stream->read($length);
        $this->logger->write($this->decorate($data, true));
        return $data;
    }

    /**
     * @{inheritDoc}
     */
    public function getContents()
    {
        $content = $this->stream->getContents();
        $this->logger->write($this->decorate($content, true));
        return $content;
    }

    /**
     * @param string $str
     * @param bool $read
     * @return string
     */
    protected function decorate($str, $read = false)
    {
        return (($read) ? '<< ' : '>> ') . $str;
    }

    /**
     * @{inheritDoc}
     */
    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }
}
