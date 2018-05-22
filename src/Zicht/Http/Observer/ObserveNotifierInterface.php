<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Observer;

interface ObserveNotifierInterface extends \SplSubject
{
    /**
     * @return \SplObserver[]
     */
    public function getObservers();

    /**
     * notify the observers with given context
     *
     * @var mixed $ctx
     */
    public function notifyWithCtx($ctx);
}