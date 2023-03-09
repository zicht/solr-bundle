<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Zicht\Bundle\SolrBundle\Manager\SolrEntityManager;
use Zicht\Bundle\SolrBundle\Manager\SolrManager;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces\Extractable;

class Subscriber implements EventSubscriber
{
    /** @var SolrManager|SolrEntityManager */
    protected $manager;

    /**
     * @param SolrManager|SolrEntityManager $manager
     */
    public function __construct(SolrManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Triggers an update in the solrmanager
     *
     * @return void
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $this->callUpdate($event);
    }

    /**
     * Triggers an update in the solrmanager
     *
     * @return void
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->callUpdate($event);
    }

    /**
     * Triggers a deletein the solrmanager
     *
     * @return void
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $this->manager->delete($event->getEntity());
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove,
        ];
    }

    /**
     * Calls the proper method in the Solr Manager to update or extract the document
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
