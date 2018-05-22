<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Message;

use Psr\Http\Message\UriInterface;
use Zicht\Http\Exception\InvalidArgumentException;

/**
 * Class Uri
 *
 * @package Zicht\Http\Message
 */
class Uri implements UriInterface
{
    /** @var array */
    private $supported = ['http' => 80, 'https' => 443];
    /** @var string */
    private $scheme;
    /** @var string */
    private $host;
    /** @var string */
    private $user;
    /** @var string */
    private $pass;
    /** @var string */
    private $port;
    /** @var string */
    private $path = '';
    /** @var string */
    private $query = '';
    /** @var string */
    private $fragment = '';


    /**
     * Uri constructor
     * .
     * @param string $uri
     */
    public function __construct($uri)
    {
        foreach (\parse_url($uri) as $name => $value) {
            $this->set($name, $value);
        }

        $this->validate();
    }

    /**
     * @param string $name
     * @param string $value
     */
    private function set($name, $value)
    {
        switch ($name) {
            case 'path':
                $this->setPath($value);
                break;
            case 'fragment':
                $this->fragment = rawurlencode(rawurldecode($value));
                break;
            case 'query':
                $this->setQuery($value);
                break;
            default:
                $this->{$name} = $value;
        }
    }

    /**
     * @{inheritDoc}
     */
    public function getScheme()
    {
        return $this->scheme ?: '';
    }

    /**
     * @{inheritDoc}
     */
    public function getAuthority()
    {
        $authority = $this->host;

        if ('' !== $userInfo = $this->getUserInfo()) {
            $authority = $userInfo . '@' . $authority;
        }

        if (null !== $this->port) {
            $authority .= ':' . $this->port;
        }

        return $authority ?: '';
    }

    /**
     * @{inheritDoc}
     */
    public function getUserInfo()
    {
        $userInfo = '';

        if (!empty($this->user)) {
            $userInfo .= $this->user;
        } else {
            return $userInfo;
        }

        if (!empty($this->pass)) {
            $userInfo .= ':' . $this->pass;
        }

        return $userInfo;
    }

    /**
     * @{inheritDoc}
     */
    public function getHost()
    {
        return $this->host ?: '';
    }

    /**
     * @{inheritDoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @{inheritDoc}
     */
    public function getPath()
    {
        if (empty($this->path)) {
            return  '';
        }

        return $this->path;
    }

    /**
     * @{inheritDoc}
     */
    public function getQuery()
    {
        if (empty($this->query)) {
            return '';
        }

        return $this->query;
    }

    /**
     * @{inheritDoc}
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @{inheritDoc}
     */
    public function withScheme($scheme)
    {
        $instance = clone $this;
        if (empty($scheme)) {
            $instance->scheme = null;
        } else {
            $instance->scheme = strtolower($scheme);
        }
        $instance->validate();
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function withUserInfo($user, $password = null)
    {
        $instance = clone $this;
        $instance->set('user', $user);
        $instance->set('pass', $password);
        $instance->validate();
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function withHost($host)
    {
        $instance = clone $this;
        if (empty($host)) {
            $instance->host = null;
        } else {
            $instance->set('host', $host);
        }
        $instance->validate();
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function withPort($port)
    {
        $instance = clone $this;
        $instance->set('port', $port);
        $instance->validate();
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function withPath($path)
    {
        $instance = clone $this;
        $instance->set('path', $path);
        $instance->validate();
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function withQuery($query)
    {
        $instance = clone $this;
        $instance->set('query', $query);
        $instance->validate();
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function withFragment($fragment)
    {
        $instance = clone $this;
        $instance->set('fragment', $fragment);
        $instance->validate();
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function __toString()
    {
        $uri = '';

        if (!empty($this->scheme)) {
            $uri .= $this->scheme . ':';
        }

        if ('' !== $authority = $this->getAuthority()) {
            $uri .= '//' . $authority;
        }


        if (!empty($authority) && !empty($this->path) && $this->path[0] !== '/') {
            $uri .= '/';
        }

        if (!empty($this->path)) {
            $uri .= preg_replace('/^\/{2,}/', '/', $this->path);
        }

        if (!empty($this->query)) {
            $uri .= '?' . $this->query;
        }
        if (!empty($this->fragment)) {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }

    private function validate()
    {
        if (null !== $this->scheme) {
            if (!isset($this->supported[$this->scheme])) {
                throw new InvalidArgumentException('Unsupported scheme.');
            }

            if (isset($this->supported[$this->scheme]) && $this->port === $this->supported[$this->scheme]) {
                $this->port = null;
            }
        }

        if (null !== $this->port) {

            if ($this->port > (2**16-1) || $this->port < 0) {
                throw new InvalidArgumentException('Port is out-of-range.');
            }

            $this->port = (int)$this->port;
        }
    }

    /**
     * @param string $path
     */
    private function setPath($path)
    {
        $this->path = implode('/', array_map('rawurlencode', array_map('rawurldecode', explode('/', $path))));
    }

    /**
     * @param string $query
     */
    private function setQuery($query)
    {
        $this->query = implode(
            '&',
            array_map(
                function($v) {
                    return implode('=', array_map('rawurlencode', array_map('rawurldecode', explode('=', $v, 2))));
                },
                explode(
                    '&',
                    $query
                )
            )
        );
    }
}
