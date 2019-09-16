<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zicht\Bundle\SolrBundle\Entity\StopWord;
use Zicht\Bundle\SolrBundle\Manager\StopWordManager;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces\Extractable;

/**
 * Class StopWordSubscriber
 */
class StopWordSubscriber implements EventSubscriber
{
    /**
     * @var StopWordManager
     */
    private $manager;

    /**
     * @param StopWordManager $container
     */
    public function __construct(StopWordManager $manager)
    {
        $this->manager = $manager;
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
        if (!$event->getEntity() instanceof StopWord) {
            return;
        }

        $this->manager->removeStopWord($event->getEntity());
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
        if (!$event->getEntity() instanceof StopWord) {
            return;
        }

        $this->manager->addStopWord($event->getEntity());
        $this->manager->getClient()->reload();
    }
}
