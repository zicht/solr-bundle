<?php
/**
 * @author    Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Subscriber implements \Doctrine\Common\EventSubscriber
{
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $this->getManager()->buildSolrIndex($event->getEntity());
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->getManager()->buildSolrIndex($event->getEntity());
    }

    public function postRemove($event)
    {
        $this->getManager()->removeSolrIndex($event->getEntity());
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
            \Doctrine\ORM\Events::postPersist,
            \Doctrine\ORM\Events::preUpdate,
            \Doctrine\ORM\Events::postRemove
        );
    }
}