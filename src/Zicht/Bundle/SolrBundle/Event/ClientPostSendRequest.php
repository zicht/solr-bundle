<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Event;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\Event;

class ClientPostSendRequest extends Event
{
    /** @var RequestInterface  */
    private $request;
    /** @var ResponseInterface  */
    private $response;

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
     * @return ClientPostSendRequest
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }
}
