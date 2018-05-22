<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Event;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\Event;

class ClientPreSendRequest extends Event
{
    /** @var RequestInterface  */
    private $request;

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
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }
}
