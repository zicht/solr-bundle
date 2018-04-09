<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapper;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zicht\Bundle\SolrBundle\Annotations\Document;
use Zicht\Bundle\SolrBundle\Annotations\Field;
use Zicht\Bundle\SolrBundle\Annotations\StaticFields;
use Zicht\Bundle\SolrBundle\Event\DocumentMapperMetadataEvent;
use Zicht\Bundle\SolrBundle\Events;
use Zicht\Bundle\SolrBundle\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class DocumentMapperMetadataFactory
 *
 * @package Zicht\Bundle\SolrBundle\Pager
 */
class DocumentMapperMetadataFactory
{
    const CACHE_SUFFIX = 'DocumentMetadata';

    /** @var array */
    private $entities;
    /** @var array  */
    private $loaded;
    /** @var CacheInterface  */
    private $cache;
    /** @var Reader */
    private $reader;
    /** @var NamingStrategy */
    private $namingStrategy;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * DocumentMapperMetadataFactory constructor.
     *
     * @param NamingStrategy $namingStrategy
     * @param CacheInterface $cache
     * @param Reader $reader
     * @param EventDispatcherInterface $dispatcher
     * @param MappingDriver[] $mappings
     */
    public function __construct(NamingStrategy $namingStrategy, CacheInterface $cache, Reader $reader, EventDispatcherInterface $dispatcher, ...$mappings)
    {
        if (null === $entities = $cache->get($this->getCacheKey())) {
            $entities = $this->getEntityInheritanceList($reader, ...$mappings);
            if ($dispatcher->hasListeners(Events::POST_BUILD_ENTITIES_LIST)) {
                $entities = $dispatcher->dispatch(Events::POST_BUILD_ENTITIES_LIST, $entities)->getList();
            }
            $cache->set($this->getCacheKey(), $entities);
        }
        $this->entities = $entities;
        $this->cache = $cache;
        $this->reader = $reader;
        $this->namingStrategy = $namingStrategy;
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @param MappingDriver[] ...$mappings
     * @return array
     */
    private function getAllEntities(...$mappings)
    {
        $entities = [];
        foreach ($mappings as $mapping) {
            $entities = array_merge($entities, $mapping->getAllClassNames());
        }
        return $entities;
    }

    /**
     * this will build a array of supported entities with the parent
     * as key and a list of children (that are a instance of the parent
     * class) when the strict annotation is set to false.
     *
     *
     * @param Reader $reader
     * @param MappingDriver[] ...$mappings
     * @return array
     */
    private function getEntityInheritanceList(Reader $reader, ...$mappings)
    {
        $allEntities = $this->getAllEntities(...$mappings);
        $entities = [];
        foreach ($allEntities as $entity) {
            /** @var Document $annotation */
            if (null !== $annotation = $reader->getClassAnnotation(new \ReflectionClass($entity), Document::class)) {
                $entities[$entity] = [];
                // search for any sub classes where this class is the parent
                if (false === $annotation->strict) {
                    foreach ($allEntities as $className) {
                        if ($className === $entity) {
                            continue;
                        }
                        if (is_a($className, $entity, true)) {
                            $entities[$entity][] = $className;
                        }
                    }
                }
            }
        }
        foreach (array_keys($entities)as $entity) {
            foreach ($entities as $parent => $children) {
                if ($parent === $entity) {
                    continue;
                }
                if (false !== $index = array_search($entity, $children)) {
                    // remove from child list because it is managed by it self and by inheritance
                    unset($entities[$parent][$index]);
                    foreach ($entities[$entity] as $index => $name) {
                        // remove because is managed by parent class
                        if (in_array($name, $children)) {
                            unset($entities[$parent][$index]);
                        }
                    }
                }
            }
        }
        return $entities;
    }

    /**
     * @return array|string[]
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @param string $className
     * @return bool
     */
    public function support($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        if (isset($this->entities[$className])) {
            return true;
        }
        foreach ($this->entities as $children) {
            if (in_array($className, $children)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \Generator|DocumentMapperMetadata
     */
    public function getAllDocumentMapperMetadata()
    {
        foreach ($this->entities as $entity => $children) {
            yield $this->getDocumentMapperMetadataForClass($entity);
            foreach ($children as $child) {
                yield $this->getDocumentMapperMetadataForClass($child);
            }
        }
    }

    /**
     * @param string $className
     * @return DocumentMapperMetadata
     */
    public function getDocumentMapperMetadataForClass($className)
    {
        if (false === $this->support($className)) {
            throw new InvalidArgumentException('Class "%s" is not a solr mapped entity', $className);
        }
        if (isset($this->loaded[$className])) {
            return $this->loaded[$className];
        }
        $cacheKey = $this->getCacheKeyForClassName($className);
        if (null === $metadata = $this->cache->get($cacheKey)) {
            $reflection = new \ReflectionClass($className);
            if (isset($this->entities[$className])) {
                $metadata = $this->newDocumentMetadata($reflection);
            } else {
                // resolve from parent
                foreach ($this->entities as $entity => $children) {
                    if (in_array($className, $children)) {
                        $metadata = $this->getDocumentMapperMetadataForClass($entity)->newWith($className);
                    }
                }
            }
            do {
                $this->readStaticFields($reflection, $metadata);
                $this->readProperties($reflection, $metadata);
            } while ($reflection = $reflection->getParentClass());
            if ($this->eventDispatcher->hasListeners(Events::LOAD_DOCUMENT_MAPPER_METADATA)) {
                $this->eventDispatcher->dispatch(
                    Events::LOAD_DOCUMENT_MAPPER_METADATA,
                    new DocumentMapperMetadataEvent($metadata)
                );
            }
            $this->cache->set($cacheKey, $metadata);
        }
        $this->loaded[$className] = $metadata;
        return $metadata;
    }

    /**
     * @param string $className
     * @return string
     */
    private function getCacheKeyForClassName($className)
    {
        return sprintf('%s[metadata][%s]', self::CACHE_SUFFIX, $className);
    }

    /**
     * @return string
     */
    private function getCacheKey()
    {
        return self::CACHE_SUFFIX. '[entities]';
    }

    /**
     * @param \ReflectionClass $reflection
     * @return DocumentMapperMetadata
     */
    private function newDocumentMetadata(\ReflectionClass $reflection)
    {
        /** @var Document $annotation */
        $annotation = $this->reader->getClassAnnotation($reflection, Document::class);
        return new DocumentMapperMetadata($reflection->getName(), $annotation->repository, $annotation->strict);
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param DocumentMapperMetadata $mapper
     */
    protected function readStaticFields(\ReflectionClass $reflectionClass, DocumentMapperMetadata $mapper)
    {
        /** @var StaticFields $annotations */
        if (null !== $annotations = $this->reader->getClassAnnotation($reflectionClass, StaticFields::class)) {
            if (is_array($annotations->value)) {
                $mapper->setStaticFields(array_merge($annotations->value, $mapper->getStaticFields()));
            }
        }
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param DocumentMapperMetadata $mapper
     */
    protected function readProperties(\ReflectionClass $reflectionClass, DocumentMapperMetadata $mapper)
    {
        foreach ($reflectionClass->getProperties() as $property) {
            /** @var Field $pa */
            if (null !== $pa = $this->reader->getPropertyAnnotation($property, Field::class)) {
                $mapper->addMapping($pa->name ?: $this->namingStrategy->propertyToColumnName($property->getName()), $property->getName());
            }
        }
    }
}
