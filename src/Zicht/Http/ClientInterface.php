<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http;

use Psr\Http\Client\ClientInterface as BaseClientInterface;
use Zicht\Http\Handler\HandlerInterface;

interface ClientInterface extends RequestFactoryInterface, BaseClientInterface
{
    /**
     * @return HandlerInterface
     */
    public function getHandler() :HandlerInterface;

    /**
     * @param HandlerInterface $handler
     * @return void
     */
    public function setHandler(HandlerInterface $handler) :void;
}
