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
use Zicht\Bundle\SolrBundle\Manager\SolrEntityManager;
use Zicht\Bundle\SolrBundle\Manager\SolrManager;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces\Extractable;

/**
 * Class Subscriber
 */
class Subscriber implements EventSubscriber
{
    /**
     * @var SolrManager|SolrEntityManager
     */
    protected $manager;

    /**
     * Construct the subscriber
     *
     * @param SolrManager|SolrEntityManager $manager
     */
    public function __construct(SolrManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Triggers an update in the solrmanager
     *
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $this->callUpdate($event);
    }


    /**
     * Triggers an update in the solrmanager
     *
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->callUpdate($event);
    }

    /**
     * Triggers a deletein the solrmanager
     *
     * @param LifecycleEventArgs $event
     * @return void
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $this->manager->delete($event->getEntity());
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
     * Calls the proper method in the Solr Manager to update or extract the document
     *
     * @param LifecycleEventArgs $event
     */
    private function callUpdate(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Extractable && is_resource($entity->getFileResource())) {
            $this->manager->extract($entity);

            return;
        }

        $this->manager->update($entity);
    }
}
