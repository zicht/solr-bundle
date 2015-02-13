<?php
/**
 * @author    Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use \Doctrine\Common\EventSubscriber;
use \Doctrine\ORM\Event\LifecycleEventArgs;
use \Doctrine\ORM\Events;
use \Symfony\Component\DependencyInjection\ContainerAwareInterface;
use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Zicht\Bundle\SolrBundle\Manager\SolrManager;

/**
 * Class Subscriber
 * @package Zicht\Bundle\SolrBundle\Manager\Doctrine
 */
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
        $this->container->get('zicht_solr.manager')->update($event->getEntity());
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->container->get('zicht_solr.manager')->update($event->getEntity());
    }

    public function preRemove(LifecycleEventArgs $event)
    {
        $this->container->get('zicht_solr.manager')->delete($event->getEntity());
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