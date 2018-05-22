<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadataFactory;

/**
 * Class LoadClassMetadataListener
 *
 * @package Zicht\Bundle\SolrBundle\EventListener
 */
class LoadClassMetadataListener
{
    /** @var DocumentMapperMetadataFactory  */
    private $factory;
    /** @var array  */
    private $events = [
        Events::postPersist,
        Events::preUpdate,
        Events::preRemove
    ];

    /**
     * LoadClassMetadataListener constructor.
     *
     * @param DocumentMapperMetadataFactory $factory
     */
    public function __construct(DocumentMapperMetadataFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param LoadClassMetadataEventArgs $args
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $metadata = $args->getClassMetadata();
        if ($this->factory->support($metadata->getReflectionClass()->getName())) {
            foreach ($this->events as $event) {
                if (false === $this->hasEventListenerFor($event, $metadata)) {
                    $metadata->addEntityListener($event, EntityListener::class, $event);
                }
            }
        }
    }

    /**
     * @param string $event
     * @param ClassMetadata $metadata
     * @return bool
     */
    protected function hasEventListenerFor($event, ClassMetadata $metadata)
    {
        if (!isset($metadata->entityListeners[$event])) {
            return false;
        }
        return in_array(EntityListener::class, array_column($metadata->entityListeners[$event], 'class'));
    }
}