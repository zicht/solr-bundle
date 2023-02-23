<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Zicht\Bundle\SolrBundle\Entity\StopWord;
use Zicht\Bundle\SolrBundle\Exception\NotFoundException;
use Zicht\Bundle\SolrBundle\Manager\StopWordManager;

class StopWordSubscriber implements EventSubscriber
{
    private bool $enabled = true;

    private StopWordManager $manager;

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
     * @return void
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $this->callUpdate($event);
    }

    /**
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

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove,
        ];
    }

    private function callUpdate(LifecycleEventArgs $event)
    {
        if (!$this->enabled || !$event->getEntity() instanceof StopWord) {
            return;
        }

        $this->manager->addStopWord($event->getEntity());
        $this->manager->getClient()->reload();
    }

    private function prepareStopword(StopWord $stopWord)
    {
        $stopWord->setValue(strtolower(trim($stopWord->getValue())));
    }
}
