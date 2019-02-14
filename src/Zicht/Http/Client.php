<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
declare(strict_types=1);

namespace Zicht\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zicht\Http\Handler\HandlerInterface;
use Zicht\Http\Message\Request;
use Zicht\Http\Observer\ObserveNotifier;
use Zicht\Http\Observer\ObserveNotifierInterface;
use Zicht\Http\Observer\ObserveRequestContext;
use Zicht\Http\Observer\ObserveResponseContext;


class Client implements ClientInterface, ObservableClientInterface
{
    /** @var HandlerInterface */
    private $handler;
    /** @var ObserveNotifierInterface  */
    private $notifier;

    /**
     * @param HandlerInterface $handler
     * @param ObserveNotifierInterface|null $notifier
     */
    public function __construct(HandlerInterface $handler, ObserveNotifierInterface $notifier = null)
    {
        $this->handler = $handler;
        $this->notifier = $notifier ?: new ObserveNotifier();
    }

    /**
     * @{inheritDoc}
     */
    public function sendRequest(RequestInterface $request) :ResponseInterface
    {
        $request = $this->notifyRequest($request);
        $response = $this->handler->send($request);
        return $this->notifyResponse($request, $response);
    }

    /**
     * @{inheritdoc}
     */
    public function createRequest($method, $uri, array $headers = [], StreamInterface $body = null) :RequestInterface
    {
        return new Request($method, $uri, $headers, $body);
    }

    /**
     * @param \SplObserver $observer
     */
    public function attachObserver(\SplObserver $observer)
    {
        $this->notifier->attach($observer);
    }

    /**
     * @param \SplObserver $observer
     */
    public function detachObserver(\SplObserver $observer)
    {
        $this->notifier->detach($observer);
    }

    /**
     * @param RequestInterface $request
     * @return RequestInterface
     */
    private function notifyRequest(RequestInterface $request) :RequestInterface
    {
        $ctx = new ObserveRequestContext($request);
        $this->notifier->notifyWithCtx($ctx);
        return $ctx->getRequest();
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function notifyResponse(RequestInterface $request, ResponseInterface $response) :ResponseInterface
    {
        $ctx = new ObserveResponseContext($request, $response);
        $this->notifier->notifyWithCtx($ctx);
        return $ctx->getResponse();
    }

    /**
     * @return HandlerInterface
     */
    public function getHandler() :HandlerInterface
    {
        return $this->handler;
    }

    /**
     * @param HandlerInterface $handler
     * @return void
     */
    public function setHandler(HandlerInterface $handler) :void
    {
        $this->handler = $handler;
    }
}
