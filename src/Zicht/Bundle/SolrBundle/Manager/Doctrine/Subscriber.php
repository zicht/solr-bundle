<?php
/**
 * @author    Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces\Extractable;

/**
 * Class Subscriber
 */
class Subscriber implements EventSubscriber
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Construct the subscriber
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Triggers an update in the solrmanager
     *
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Extractable && $entity->getFile() !== null) {
            $this->container->get('zicht_solr.manager')->extract($entity);
            return;
        }

        $this->container->get('zicht_solr.manager')->update($entity);
    }


    /**
     * Triggers an update in the solrmanager
     *
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Extractable && $entity->getFile() !== null) {
            $this->container->get('zicht_solr.manager')->extract($entity);
            return;
        }

        $this->container->get('zicht_solr.manager')->update($entity);
    }

    /**
     * Triggers a deletein the solrmanager
     *
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $this->container->get('zicht_solr.manager')->delete($event->getEntity());
    }

    /**
     * @{inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove
        );
    }
}
