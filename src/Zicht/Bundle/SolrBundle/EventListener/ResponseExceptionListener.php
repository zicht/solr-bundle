<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Zicht\Bundle\SolrBundle\Exception\HttpResponseException;

/**
 * Class ResponseExceptionListener
 * @package Zicht\Bundle\SolrBundle\EventListener
 */
class ResponseExceptionListener
{
    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ((null !== $exception = $event->getException()) &&  $exception instanceof HttpResponseException) {
            $event->setResponse($exception->getResponse());
        }
    }
}
