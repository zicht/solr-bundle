<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Request
 *
 * @package Zicht\Http\Message
 */
class Request implements RequestInterface
{
    use MessageTrait;

    /** @var UriInterface  */
    private $uri;
    /** @var string */
    private $method;

    /**
     * Request constructor.
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param StreamInterface|null $body
     */
    public function __construct($method, $uri, array $headers = [], StreamInterface $body = null)
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $this->header = [];

        foreach ($headers as $name => $value) {
            if (is_scalar($value)) {
                $this->header[$name] = [$value];
            }
            if (is_array($value)) {
                $this->header[$name] = $value;
            }
        }

        $this->method = $method;
        $this->uri = $uri;
        $this->body = $body;
    }

    /**
     * @{inheritDoc}
     */
    public function getRequestTarget()
    {

        if ('' === $target = $this->uri->getPath()) {
            $target = '/';
        }

        if ("" !== $query = $this->uri->getQuery()) {
            $target .= '?' . $query;
        }

        return $target;
    }

    /**
     * @{inheritDoc}
     */
    public function withRequestTarget($requestTarget)
    {
        $instance = clone $this;
        $parts = parse_url($requestTarget);
        if (isset($parts['path'])) {
            $instance->uri = $instance->uri->withPath($parts['path']);
        }
        if (isset($parts['query'])) {
            $instance->uri = $instance->uri->withQuery($parts['query']);
        }
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @{inheritDoc}
     */
    public function withMethod($method)
    {
        $instance = clone $this;
        $instance->method = $method;
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @{inheritDoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $instance = clone $this;
        $instance->uri = $uri;

        if (!$preserveHost) {
            if ('' === $host = $this->uri->getHost()) {
                return $instance;
            }
            if (($port = $this->uri->getPort()) !== null) {
                $host .= ':' . $port;
            }
            return $instance->withMethod($host);
        }

        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function __clone()
    {
        $this->uri = clone $this->uri;
    }
}