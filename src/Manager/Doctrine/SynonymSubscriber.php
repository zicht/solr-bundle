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

/**
 * Class SynonymSubscriber
 */
class SynonymSubscriber implements EventSubscriber
{
    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var SynonymManager
     */
    private $manager;

    /**
     * @param SynonymManager $container
     */
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
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        if (!$event->getEntity() instanceof Synonym) {
            return;
        }

        $this->prepareSynonym($event->getEntity());
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
        if (!$event->getEntity() instanceof Synonym) {
            return;
        }

        $this->prepareSynonym($event->getEntity());
        $this->callUpdate($event);
    }

    /**
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        if (!$this->enabled || !$event->getEntity() instanceof Synonym) {
            return;
        }

        try {
            $this->manager->removeSynonym($event->getEntity());
            $this->manager->getClient()->reload();
        } catch (NotFoundException $e) {
            // Synonym was not found, so isn't already there. Nothing to be done...
        }
    }

    /** {@inheritDoc} */
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
        if (!$this->enabled || !$event->getEntity() instanceof Synonym) {
            return;
        }

        // If the 'identifier' or 'managed' was changed, we need to remove the old entry first.
        if ($event instanceof PreUpdateEventArgs && (
            ($event->hasChangedField('identifier') && $event->getNewValue('identifier') !== $event->getOldValue('identifier'))
            || ($event->hasChangedField('managed') && $event->getNewValue('managed') !== $event->getOldValue('managed'))
        )) {
            /** @var Synonym $oldEntity */
            $oldEntity = clone $event->getEntity();
            $oldEntity->setIdentifier($event->hasChangedField('identifier') ? $event->getOldValue('identifier') : $oldEntity->getIdentifier());
            $oldEntity->setManaged($event->hasChangedField('managed') ? $event->getOldValue('managed') : $oldEntity->getManaged());
            try {
                $this->manager->removeSynonym($oldEntity);
            } catch (NotFoundException $e) {
                // Synonym was not found, so isn't already there. Do nothing, proceed with adding it below...
            }
        }

        $this->manager->addSynonym($event->getEntity());
        $this->manager->getClient()->reload();
    }

    /**
     * @param Synonym $synonym
     */
    private function prepareSynonym(Synonym $synonym)
    {
        $synonym->setIdentifier(strtolower(trim($synonym->getIdentifier())));
        $synonym->setValue(strtolower(trim($synonym->getValue())));
    }
}
