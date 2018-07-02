<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Event;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class HttpClientResponseEvent
 *
 * @package Zicht\Bundle\SolrBundle\Event
 */
class HttpClientResponseEvent extends Event
{
    /** @var RequestInterface  */
    private $request;
    /** @var ResponseInterface  */
    private $response;

    /**
     * HttpClientResponseEvent constructor.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param ResponseInterface $response
     * @return HttpClientResponseEvent
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }
}
