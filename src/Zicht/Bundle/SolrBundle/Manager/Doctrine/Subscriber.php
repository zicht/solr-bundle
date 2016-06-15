<?php
/**
 * @author    Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zicht\Bundle\SolrBundle\Event\ChangeSet;
use Zicht\Bundle\SolrBundle\Event\SolrFilterChangesEvent;
use Zicht\Bundle\SolrBundle\Event\SolrMapChangesEvent;
use Zicht\Bundle\SolrBundle\Event\SolrPreUpdateEvent;
use Zicht\Bundle\SolrBundle\Manager\SolrManager;

/**
 * Class Subscriber
 * @package Zicht\Bundle\SolrBundle\Manager\Doctrine
 */
class Subscriber implements EventSubscriber
{
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->changeSet = new ChangeSet();
    }

    /**
     * @param boolean $enabled
     * @deprecated Enable or disable the manager in stead.
     */
    public function setEnabled($enabled)
    {
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $this->changeSet->add('insert', $event->getEntity());
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->changeSet->add('update', $event->getEntity());
    }

    public function preRemove(LifecycleEventArgs $event)
    {
        $this->changeSet->add('delete', $event->getEntity());
    }


    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (count($this->changeSet->getChanges())) {
            $changeSet = $this->changeSet;
            $this->changeSet = new ChangeSet();

            // fire events to allow other bundles to influence the actual changes:
            // -- first, allow listeners to remove changes
            $this->container->get('event_dispatcher')->dispatch('zicht.solr.filter.changes', new SolrFilterChangesEvent($changeSet));

            // -- then, allow listeners to map changes (i.e., "if this object is changed, then that other object should be indexed")
            $this->container->get('event_dispatcher')->dispatch('zicht.solr.map.changes', new SolrMapChangesEvent($changeSet));

            $changes = $changeSet->getChanges();

            if (count($changes)) {
                $solrManager = $this->container->get('zicht_solr.manager');
                foreach ($changes as list($type, $entity)) {
                    switch ($type) {
                        case 'delete':
                            $solrManager->delete($entity);
                            break;
                        case 'update':
                        case 'insert':
                            $solrManager->update($entity);
                            break;
                    }
                }
            }
        }
    }


    /**
     * @{inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove,
            Events::postFlush
        );
    }
}