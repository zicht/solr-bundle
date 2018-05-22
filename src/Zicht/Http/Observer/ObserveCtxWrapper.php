<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Observer;

/**
 * Class ObserveCtxWrapper
 *
 * @package Zicht\Http\Observer
 */
class ObserveCtxWrapper implements \SplSubject
{
    /** @var ObserveNotifierInterface */
    private $subject;
    /** @var mixed */
    private $ctx;

    /**
     * ObserveCtxWrapper constructor.
     *
     * @param ObserveNotifierInterface $subject
     * @param mixed $ctx
     */
    public function __construct(ObserveNotifierInterface $subject, $ctx)
    {
        $this->subject = $subject;
        $this->ctx = $ctx;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(\SplObserver $observer)
    {
        $this->subject->attach($observer);
    }

    /**
     * {@inheritDoc}
     */
    public function detach(\SplObserver $observer)
    {
        $this->subject->detach($observer);
    }

    /**
     * {@inheritDoc}
     */
    public function notify()
    {
        foreach ($this->subject->getObservers() as $observer) {
            $observer->update($this);
        }
    }

    /**
     * @return ObserverContext
     */
    public function getContext()
    {
        return $this->ctx;
    }

    /**
     * @return ObserveNotifierInterface
     */
    public function getSubject()
    {
        return $this->subject;
    }
}
