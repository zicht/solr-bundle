<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Observer;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ObserveRequestContext
 *
 * @package Zicht\Http\Observer
 */
class ObserveRequestContext
{
    /** @var RequestInterface */
    private $request;

    /**
     * ObserveRequestContext constructor.
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
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
}
