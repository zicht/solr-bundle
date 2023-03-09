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
        $object = $event->getObject();
        if (!$object instanceof StopWord) {
            return;
        }

        $this->prepareStopword($object);
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
        $object = $event->getObject();
        if (!$object instanceof StopWord) {
            return;
        }

        $this->prepareStopword($object);
        $this->callUpdate($event);
    }

    /**
     * @return void
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if (!$this->enabled || !$object instanceof StopWord) {
            return;
        }

        try {
            $this->manager->removeStopWord($object);
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
        $object = $event->getObject();
        if (!$this->enabled || !$object instanceof StopWord) {
            return;
        }

        $this->manager->addStopWord($object);
        $this->manager->getClient()->reload();
    }

    private function prepareStopword(StopWord $stopWord)
    {
        $stopWord->setValue(strtolower(trim($stopWord->getValue())));
    }
}
