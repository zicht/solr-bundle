<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zicht\Bundle\SolrBundle\Entity\StopWord;
use Zicht\Bundle\SolrBundle\Entity\Synonym;
use Zicht\Bundle\SolrBundle\Manager\StopWordManager;
use Zicht\Bundle\SolrBundle\Manager\SynonymManager;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces\Extractable;

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

        $this->manager->removeSynonym($event->getEntity());
        $this->manager->getClient()->reload();
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
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
            $this->manager->removeSynonym($oldEntity);
        }

        $this->manager->addSynonym($event->getEntity());
        $this->manager->getClient()->reload();
    }
}
