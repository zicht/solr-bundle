<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zicht\Http\Exception\InvalidArgumentException;

/**
 * Class Response
 *
 * @package Zicht\Http\Message
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    /** @var int  */
    private $status;
    /** @var string  */
    private $phrase;

    /**
     * Response constructor.
     *
     * @param int $status
     * @param string $phrase
     * @param string $protocol
     * @param array $headers
     * @param StreamInterface|null $body
     */
    public function __construct($status, $phrase, $protocol, array $headers = [], StreamInterface $body = null)
    {
        $this->status = $status;
        $this->protocol = $protocol;
        $this->phrase = $phrase;
        $this->header = $headers;
        $this->body = $body;
    }

    /**
     * @{inheritDoc}
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * @{inheritDoc}
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        if (false == @preg_match('/\d{3}/', $code)) {
            throw new InvalidArgumentException('Invalid status code ' . $code);
        }

        $instance = clone $this;
        $instance->status = $code;
        $instance->phrase = $reasonPhrase;
        return $instance;
    }

    /**
     * @{inheritDoc}
     */
    public function getReasonPhrase()
    {
        return $this->phrase;
    }
}
