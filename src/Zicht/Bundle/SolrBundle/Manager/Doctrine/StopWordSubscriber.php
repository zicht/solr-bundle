<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Zicht\Bundle\SolrBundle\Entity\StopWord;
use Zicht\Bundle\SolrBundle\Exception\NotFoundException;
use Zicht\Bundle\SolrBundle\Manager\StopWordManager;

/**
 * Class StopWordSubscriber
 */
class StopWordSubscriber implements EventSubscriber
{
    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var StopWordManager
     */
    private $manager;

    /**
     * @param StopWordManager $manager
     */
    public function __construct(StopWordManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        if (!$event->getEntity() instanceof StopWord) {
            return;
        }

        $this->prepareStopword($event->getEntity());
    }

    /**
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $this->callUpdate($event);
    }

    /**
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        if (!$event->getEntity() instanceof StopWord) {
            return;
        }

        $this->prepareStopword($event->getEntity());
        $this->callUpdate($event);
    }

    /**
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        if (!$this->enabled || !$event->getEntity() instanceof StopWord) {
            return;
        }

        try {
            $this->manager->removeStopWord($event->getEntity());
            $this->manager->getClient()->reload();
        } catch (NotFoundException $e) {
            // Stopword was not found, so isn't already there. Nothing to be done...
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove
        );
    }

    /**
     * @param LifecycleEventArgs $event
     */
    private function callUpdate(LifecycleEventArgs $event)
    {
        if (!$this->enabled || !$event->getEntity() instanceof StopWord) {
            return;
        }

        $this->manager->addStopWord($event->getEntity());
        $this->manager->getClient()->reload();
    }

    /**
     * @param StopWord $stopWord
     */
    private function prepareStopword(StopWord $stopWord)
    {
        $stopWord->setValue(strtolower(trim($stopWord->getValue())));
    }
}
