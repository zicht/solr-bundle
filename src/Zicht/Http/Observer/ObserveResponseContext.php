<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Observer;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ObserveResponseContext
 *
 * @package Zicht\Http\Observer
 */
class ObserveResponseContext
{
    /** @var RequestInterface */
    private $request;
    /** @var ResponseInterface */
    private $response;

    /**
     * ObserveResponseContext constructor.
     *
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param RequestInterface $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }
}
