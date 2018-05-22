<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

interface RequestFactoryInterface
{
    /**
     * @param string $method
     * @param string|UriInterface $uri
     * @param array $headers
     * @param StreamInterface|null $body
     *
     * @return RequestInterface
     */
    public function createRequest($method, $uri, array $headers = [], StreamInterface $body = null);
}
