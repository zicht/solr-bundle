<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
declare(strict_types=1);

namespace Zicht\Bundle\SolrBundle\Service;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zicht\Bundle\SolrBundle\Authorization\DecisionManagerInterface;
use Zicht\Bundle\SolrBundle\Event\MapEvent;
use Zicht\Bundle\SolrBundle\Event\SolrUpdateEvent;
use Zicht\Bundle\SolrBundle\Events;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadataFactory;
use Zicht\Bundle\SolrBundle\Mapping\DocumentRepositoryInterface;
use Zicht\Bundle\SolrBundle\Mapping\IdGeneratorDefault;
use Zicht\Bundle\SolrBundle\QueryBuilder\Update;

/**
 * Class SolrManager
 * @package Zicht\Bundle\SolrBundle\Service
 */
class SolrManager
{
    /** @var SolrClient  */
    private $client;
    /** @var  DocumentMapperMetadataFactory */
    private $documentMetadataFactory;
    /** @var ObjectStorage */
    private $objectStorage;
    /** @var EventDispatcherInterface  */
    private $dispatcher;
    /** @var DecisionManagerInterface  */
    private $decisionManager;

    /**
     * @param SolrClient $client
     * @param DocumentMapperMetadataFactory $documentMetadataFactory
     * @param ObjectStorage|null $objectStorage
     */
    public function __construct(SolrClient $client, DocumentMapperMetadataFactory $documentMetadataFactory, ObjectStorage $objectStorage = null, EventDispatcherInterface $dispatcher, DecisionManagerInterface $decisionManager)
    {
        $this->client = $client;
        $this->dispatcher = $dispatcher;
        $this->objectStorage = $objectStorage ?: new ObjectStorage();
        $this->documentMetadataFactory = $documentMetadataFactory;
        $this->decisionManager = $decisionManager;
    }

    /**
     * @return DocumentMapperMetadataFactory
     */
    public function getDocumentMapperMetadataFactory() :DocumentMapperMetadataFactory
    {
        return $this->documentMetadataFactory;
    }

    /**
     * @param string $className
     * @return DocumentMapperMetadata
     */
    public function getDocumentMapperMetadata($className) :DocumentMapperMetadata
    {
        return $this->documentMetadataFactory->getDocumentMapperMetadataForClass($className);
    }

    /**
     * @param object|string $entity
     * @return null|DocumentRepositoryInterface
     */
    public function getRepository($entity) :?DocumentRepositoryInterface
    {
        $meta = $this->getDocumentMapperMetadata($entity);

        if (null !== $repo = $meta->getRepository()) {
            return $this->objectStorage->get($repo, ObjectStorageScopes::SCOPE_MAPPING_REPOSITORY);
        }

        return null;
    }

    /**
     * Delete an entity
     *
     * @param mixed $entities
     */
    public function delete(...$entities) :void
    {
        $update = new Update();
        foreach ($entities as $entity) {
            $this->removeEntity($update, $entity);
        }
        $this->persis($update);
    }

    /**
     * Update an entity
     *
     * @param mixed... $entities
     */
    public function update(...$entities)  :void
    {
        $update = new Update();
        foreach ($entities as $entity) {
            $this->updateEntity($update, $entity);
        }
        $this->persis($update);
    }

    /**
     * @param Update $update
     * @param object $entity
     */
    public function updateEntity(Update $update, $entity) :void
    {
        $meta = $this->getDocumentMapperMetadata($entity);
        if ($this->decisionManager->decide(DecisionManagerInterface::TRANSACTION_UPDATE, $meta, $entity)) {
            $update->add($this->marshall($meta, $entity), $meta->getParams());
            $this->dispatchSolrEvents($update, $meta, $entity, Events::SOLR_POST_UPDATE, 'post_solr_update');
        }
    }

    /**
     * @param Update $update
     * @param object $entity
     */
    public function removeEntity(Update $update, $entity) :void
    {
        $meta = $this->getDocumentMapperMetadata($entity);

        if ($this->decisionManager->decide(DecisionManagerInterface::TRANSACTION_DELETE, $meta, $entity)) {
            $update->deleteOne($this->getDocumentId($meta, $entity));
            $this->dispatchSolrEvents($update, $meta, $entity, Events::SOLR_POST_DELETE, 'post_solr_delete');
        }
    }

    /**
     * @param Update $update
     */
    public function persis(Update $update) :void
    {
        $update->commit();
        $this->client->update($update);
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param object $entity
     * @return array
     */
    private function marshall(DocumentMapperMetadata $meta, $entity) :array
    {
        $transformers = $this->getTransFormers($meta);
        $data = $this->dispatchMappingEvents(['id' => $this->getDocumentId($meta, $entity)], $meta, $entity, Events::DOCUMENT_MAPPING_PRE, 'pre_map');

        foreach ($meta->getMapping() as $mapping) {
            $mapping->append($entity, $data, $this->objectStorage);
        }

        foreach ($data as $name => &$value) {
            if (array_key_exists($name, $transformers)) {
                foreach ($transformers[$name] as $transformer) {
                    $value = $transformer($value);
                }
            }
        }

        $data = $this->dispatchMappingEvents($data, $meta, $entity, Events::DOCUMENT_MAPPING_POST, 'post_map');

        return $data;
    }

    /**
     * @param array $data
     * @param DocumentMapperMetadata $meta
     * @param object $entity
     * @param string $eventName
     * @param string  $key
     *
     * @return array
     */
    private function dispatchMappingEvents(array $data, DocumentMapperMetadata $meta, $entity, $eventName, $key) :array
    {
        return $this->dispatchEvents(new MapEvent($data, $entity, $meta), $eventName, $meta, $key)->getData();
    }

    /**
     * @param Update $update
     * @param DocumentMapperMetadata $meta
     * @param object $entity
     * @param string $eventName
     * @param string $key
     */
    private function dispatchSolrEvents(Update $update, DocumentMapperMetadata $meta, $entity, $eventName, $key) :void
    {
        $this->dispatchEvents(new SolrUpdateEvent($update, $entity), $eventName, $meta, $key);
    }

    /**
     * @param Event $event
     * @param string $eventName
     * @param DocumentMapperMetadata $meta
     * @param string $key
     *
     * @return Event
     */
    private function dispatchEvents(Event $event, $eventName, DocumentMapperMetadata $meta, $key) :Event
    {
        $method = str_replace('_', '', ucwords($key, '_'));

        if ($this->dispatcher->hasListeners($eventName)) {
            $event = $this->dispatcher->dispatch($eventName, $event);
        }

        if ((null !== $events = $meta->getOption('events')) && isset($events[$key])) {
            foreach ((array)$events[$key] as $class) {
                $this->objectStorage->get($class, ObjectStorageScopes::SCOPE_DOCUMENT_LISTENER)->{$method}($event);
                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }

        return $event;
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @return array
     */
    private function getTransFormers(DocumentMapperMetadata $meta) :array
    {
        $ret = [];
        foreach ($meta->getTransformers() as $property => $classes) {
            foreach ($classes as $className) {
                $ret[$property][] = $this->objectStorage->get($className, ObjectStorageScopes::SCOPE_MAPPING_FIELD_TRANSFORMER);
            }
        }
        return $ret;
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param object $entity
     * @return string
     */
    private function getDocumentId(DocumentMapperMetadata $meta, $entity) :string
    {
        if (null === $generator = $meta->getIdGenerator()) {
            $generator = IdGeneratorDefault::class;
        }

        return $this->objectStorage->get($generator, ObjectStorageScopes::SCOPE_DOCUMENT_ID_GENERATOR)->generate($entity);
    }
}
