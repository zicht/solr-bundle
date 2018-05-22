<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http;

interface ObservableClientInterface
{
    /**
     * @param \SplObserver $observer
     */
    public function attachObserver(\SplObserver $observer);

    /**
     * @param \SplObserver $observer
     */
    public function detachObserver(\SplObserver $observer);
}