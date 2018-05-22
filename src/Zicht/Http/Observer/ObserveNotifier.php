<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Observer;

/**
 * Class ObserveNotifier
 *
 * @package Zicht\Http\Observer
 */
class ObserveNotifier implements ObserveNotifierInterface
{
    /** @var \SplObjectStorage */
    private $observers;

    /**
     * RequestObservable constructor.
     */
    public function __construct()
    {
        $this->observers = new \SplObjectStorage();
    }

    /**
     * {@inheritDoc}
     */
    public function attach(\SplObserver $observer)
    {
        $this->observers->attach($observer);
    }

    /**
     * {@inheritDoc}
     */
    public function detach(\SplObserver $observer)
    {
        $this->observers->detach($observer);
    }

    /**
     * {@inheritDoc}
     */
    public function notify()
    {
        foreach ($this->getObservers() as $observer) {
            $observer->update($this);
        }
    }


    /**
     * {@inheritDoc}
     */
    public function notifyWithCtx($ctx)
    {
        $subject = new ObserveCtxWrapper($this, $ctx);
        $subject->notify();
    }

    /**
     * @return \Generator|\SplObserver[]
     */
    public function getObservers()
    {
        foreach ($this->observers as $observer) {
            yield $observer;
        }
    }
}
