<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Handler;

use Psr\Http\Message\StreamInterface;

/**
 * Interface HandlerDebugInterface
 * @package Zicht\Http\Handler
 */
interface HandlerDebugInterface
{
    /**
     * @param bool|StreamInterface $debug
     */
    public function setDebug($debug);

    /**
     * @return bool
     */
    public function isDebug();

    /**
     * @return StreamInterface
     */
    public function getLog();
}
