<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * Trait MessageTrait
 *
 * @package Zicht\Http\Message
 */
trait MessageTrait
{
    use HeaderTrait;

    /** @var string */
    private $protocol = '1.1';
    /** @var StreamInterface  */
    private $body ;

    /**
     * @{inheritDoc}
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * @{inheritDoc}
     */
    public function withProtocolVersion($version)
    {
        $instance = clone $this;
        $instance->protocol = $version;
        return $instance;
    }


    /**
     * @{inheritDoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @{inheritDoc}
     */
    public function withBody(StreamInterface $body)
    {
        $instance = clone $this;
        $instance->body = $body;
        return $instance;
    }
}