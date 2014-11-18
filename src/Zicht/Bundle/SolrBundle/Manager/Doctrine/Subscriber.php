<?php
/**
 * @author    Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use \Doctrine\Common\EventSubscriber;
use \Doctrine\ORM\Event\LifecycleEventArgs;
use \Doctrine\ORM\Events;
use \Symfony\Component\DependencyInjection\ContainerInterface;

class Subscriber implements EventSubscriber
{
    protected $enabled = true;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        if ($this->getEnabled()) {
            $this->getManager()->buildSolrIndex($event->getEntity());
        }
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        if ($this->getEnabled()) {
            $this->getManager()->buildSolrIndex($event->getEntity());
        }
    }

    public function preRemove(LifecycleEventArgs $event)
    {
        if ($this->getEnabled()) {
            $this->getManager()->removeSolrIndex($event->getEntity());
        }
    }

    /**
     * @return \Zicht\Bundle\SolrBundle\Manager\SolrManager
     */
    public function getManager()
    {
        return $this->container->get('zicht_solr.solr_manager');
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove
        );
    }
}