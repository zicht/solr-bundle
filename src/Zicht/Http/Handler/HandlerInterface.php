<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Interface HandlerInterface
 * @package Zicht\Http\Handler
 */
interface HandlerInterface
{
    /**
     * AdapterInterface constructor.
     *
     * @param UriInterface $host
     * @param array $options
     */
    public function __construct(UriInterface $host, array $options = []);

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function send(RequestInterface $request);
}
