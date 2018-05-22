<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    /**
     * PSR-18 is still in draft, but for the future we implement
     * a copy of the client interface so it can be easily overwritten
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request);
}