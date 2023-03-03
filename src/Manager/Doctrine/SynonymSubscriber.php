<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Zicht\Bundle\SolrBundle\Entity\Synonym;
use Zicht\Bundle\SolrBundle\Exception\NotFoundException;
use Zicht\Bundle\SolrBundle\Manager\SynonymManager;

class SynonymSubscriber implements EventSubscriber
{
    private bool $enabled = true;

    private SynonymManager $manager;

    public function __construct(SynonymManager $manager)
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
        if (!$object instanceof Synonym) {
            return;
        }

        $this->prepareSynonym($object);
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
        if (!$object instanceof Synonym) {
            return;
        }

        $this->prepareSynonym($object);
        $this->callUpdate($event);
    }

    /**
     * @return void
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if (!$this->enabled || !$object instanceof Synonym) {
            return;
        }

        try {
            $this->manager->removeSynonym($object);
            $this->manager->getClient()->reload();
        } catch (NotFoundException $e) {
            // Synonym was not found, so isn't already there. Nothing to be done...
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
        if (!$this->enabled || !$object instanceof Synonym) {
            return;
        }

        // If the 'identifier' or 'managed' was changed, we need to remove the old entry first.
        if ($event instanceof PreUpdateEventArgs && (
            ($event->hasChangedField('identifier') && $event->getNewValue('identifier') !== $event->getOldValue('identifier'))
            || ($event->hasChangedField('managed') && $event->getNewValue('managed') !== $event->getOldValue('managed'))
        )) {
            $oldEntity = clone $object;
            $oldEntity->setIdentifier($event->hasChangedField('identifier') ? $event->getOldValue('identifier') : $oldEntity->getIdentifier());
            $oldEntity->setManaged($event->hasChangedField('managed') ? $event->getOldValue('managed') : $oldEntity->getManaged());
            try {
                $this->manager->removeSynonym($oldEntity);
            } catch (NotFoundException $e) {
                // Synonym was not found, so isn't already there. Do nothing, proceed with adding it below...
            }
        }

        $this->manager->addSynonym($object);
        $this->manager->getClient()->reload();
    }

    private function prepareSynonym(Synonym $synonym)
    {
        $synonym->setIdentifier(strtolower(trim($synonym->getIdentifier())));
        $synonym->setValue(strtolower(trim($synonym->getValue())));
    }
}
