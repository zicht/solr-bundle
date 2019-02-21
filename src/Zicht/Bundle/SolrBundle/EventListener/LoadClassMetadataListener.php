<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
declare(strict_types=1);

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
    /** @var string */
    private $listener;

    /**
     * LoadClassMetadataListener constructor.
     *
     * @param DocumentMapperMetadataFactory $factory
     * @param string $listener
     */
    public function __construct(DocumentMapperMetadataFactory $factory, $listener = EntityListener::class)
    {
        $this->factory = $factory;
        $this->listener = $listener;
    }

    /**
     * @param LoadClassMetadataEventArgs $args
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args) :void
    {
        $metadata = $args->getClassMetadata();
        if ($this->factory->support($metadata->getReflectionClass()->getName())) {
            foreach ($this->getEvent() as $event) {
                if (false === $this->hasEventListenerFor($event, $metadata)) {
                    $metadata->addEntityListener($event, $this->listener, $event);
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getEvent() :array
    {
        return [
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove
        ];
    }

    /**
     * @param string $event
     * @param ClassMetadata $metadata
     * @return bool
     */
    private function hasEventListenerFor($event, ClassMetadata $metadata) :bool
    {
        if (!isset($metadata->entityListeners[$event])) {
            return false;
        }

        return in_array($this->listener, array_column($metadata->entityListeners[$event], 'class'));
    }
}
