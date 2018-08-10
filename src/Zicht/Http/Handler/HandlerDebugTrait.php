<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Handler;

use Psr\Http\Message\StreamInterface;
use Zicht\Http\Stream\ResourceStream;

/**
 * Trait HandlerDebugTrait
 * @package Zicht\Http\Handler
 */
trait HandlerDebugTrait
{
    /** @var StreamInterface */
    private $logger;
    /** @var bool */
    private $isDebug;

    /**
     * @param bool|StreamInterface $debug
     */
    public function setDebug($debug)
    {
        if ($debug instanceof StreamInterface) {
            $this->logger = $debug;
            return;
        }

        if ($debug) {
            $this->logger = new ResourceStream(fopen('php://temp', 'r+'));
        } else {
            $this->logger = null;
        }
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return null !== $this->logger;
    }

    /**
     * @return StreamInterface
     */
    public function getLog()
    {
        return $this->logger;
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        if ($this->isDebug()) {
            $this->logger->write($message . "\n");
        }
    }
}
