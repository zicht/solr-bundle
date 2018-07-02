<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Http;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zicht\Bundle\SolrBundle\Event\HttpClientRequestEvent;
use Zicht\Bundle\SolrBundle\Event\HttpClientResponseEvent;
use Zicht\Bundle\SolrBundle\Events;
use Zicht\Http\Observer\ObserveCtxWrapper;
use Zicht\Http\Observer\ObserveRequestContext;
use Zicht\Http\Observer\ObserveResponseContext;

class ObserverBridge implements \SplObserver
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * Observer constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritdoc
     */
    public function update(\SplSubject $subject)
    {
        if ($subject instanceof ObserveCtxWrapper) {
            $ctx = $subject->getContext();

            if ($ctx instanceof ObserveRequestContext) {
                $this->dispatch(Events::HTTP_CLIENT_REQUEST, HttpClientRequestEvent::class, $ctx->getRequest());
            }

            if ($ctx instanceof ObserveResponseContext) {
                $this->dispatch(Events::HTTP_CLIENT_RESPONSE, HttpClientResponseEvent::class, $ctx->getRequest(), $ctx->getResponse());
            }
        }
    }

    /**
     * @param string $event
     * @param string $class
     * @param mixed[] ...$args
     */
    private function dispatch($event, $class, ...$args)
    {
        if ($this->dispatcher->hasListeners($event)) {
            $this->dispatcher->dispatch($event, new $class(...$args));
        }
    }
}
