<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Zicht\Bundle\SolrBundle\Service\SolrManager;

/**
 * Class EntityListener
 *
 * @package Zicht\Bundle\SolrBundle\EventListener
 */
class EntityListener
{
    /** @var SolrManager */
    private $manager;

    /**
     * EntityListener constructor.
     *
     * @param SolrManager $manager
     */
    public function __construct(SolrManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param $entity
     * @param LifecycleEventArgs $event
     */
    public function postPersist($entity, LifecycleEventArgs $event)
    {
        $this->getManager()->update($entity);
    }

    /**
     * @param $entity
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate($entity, PreUpdateEventArgs $event)
    {
        $this->getManager()->update($entity);
    }

    /**
     * @param $entity
     * @param LifecycleEventArgs $event
     */
    public function preRemove($entity, LifecycleEventArgs $event)
    {
        $this->getManager()->delete($entity);
    }

    /**
     * @return SolrManager
     */
    public function getManager() :SolrManager
    {
        return $this->manager;
    }
}
