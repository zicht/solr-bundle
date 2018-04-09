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
use Psr\SimpleCache\CacheInterface;
use Zicht\Bundle\SolrBundle\Annotations\Field;
use Zicht\Bundle\SolrBundle\Annotations\StaticFields;
use Zicht\Bundle\SolrBundle\Event\DocumentMetadataEvent;
use Zicht\Bundle\SolrBundle\Events;
use Zicht\Bundle\SolrBundle\Exception\InvalidArgumentException;

/**
 * Class DocumentMetadataFactory
 *
 * @package Zicht\Bundle\SolrBundle\Pager
 */
class DocumentMetadataFactory
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
     * MetdataFactory constructor.
     *
     * @param NamingStrategy $namingStrategy
     * @param CacheInterface $cache
     * @param Reader $reader
     * @param EventDispatcherInterface $eventDispatcher
     * @param MappingDriver[] $mappings
     */
    public function __construct(NamingStrategy $namingStrategy, CacheInterface $cache, Reader $reader, EventDispatcherInterface $eventDispatcher, ...$mappings)
    {
        if (null === $entities = $cache->get($this->getCacheKey())) {
            $entities = [];
            foreach ($mappings as $mapping) {
                foreach ($mapping->getAllClassNames() as $entity) {
                    if (null !== $reader->getClassAnnotation(new \ReflectionClass($entity), Document::class)) {
                        $entities[] = $entity;
                    }
                }
            }
            $cache->set($this->getCacheKey(), $entities);
        }
        $this->entities = $entities;
        $this->cache = $cache;
        $this->reader = $reader;
        $this->namingStrategy = $namingStrategy;
        $this->eventDispatcher = $eventDispatcher;
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
    public function supports($className)
    {
        return in_array($className, $this->entities);
    }

    /**
     * @param string $className
     * @return mixed|DocumentMetadata
     */
    public function getDocumentMetadataForClass($className)
    {
        if (!in_array($className, $this->entities)) {
            throw new InvalidArgumentException('Class "%s" is not a solr mapped entity', $className);
        }
        if (isset($this->loaded[$className])) {
            return $this->loaded[$className];
        }
        $cacheKey = $this->getCacheKeyForClassName($className);
        if (null === $metadata = $this->cache->get($cacheKey)) {
            $reflection = new \ReflectionClass($className);
            $metadata = $this->newDocumentMetadata($reflection);
            do {
                $this->readStaticFields($reflection, $metadata);
                $this->readProperties($reflection, $metadata);
            } while ($reflection = $reflection->getParentClass());
            if ($this->eventDispatcher->hasListeners(Events::LOAD_DOCUMENT_METADATA)) {
                $this->eventDispatcher->dispatch(Events::LOAD_DOCUMENT_METADATA, new DocumentMetadataEvent($metadata));
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
     * @return DocumentMetadata
     */
    private function newDocumentMetadata(\ReflectionClass $reflection)
    {
        /** @var Document $annotation */
        $annotation = $this->reader->getClassAnnotation($reflection, Document::class);
        return new DocumentMetadata($reflection->getName(), $annotation->repository, $annotation->strict);
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param DocumentMetadata $mapper
     */
    protected function readStaticFields(\ReflectionClass $reflectionClass, DocumentMetadata $mapper)
    {
        /** @var StaticFields $annotations */
        if (null !== $annotations = $this->reader->getClassAnnotation($reflectionClass, StaticFields::class)) {
            if (is_array($annotations->value)) {
                $mapper->setStaticFields(array_merge($annotations->value,$mapper->getStaticFields()));
            }
        }
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param DocumentMetadata $mapper
     */
    protected function readProperties(\ReflectionClass $reflectionClass, DocumentMetadata $mapper)
    {
        foreach ($reflectionClass->getProperties() as $property) {
            /** @var Field $pa */
            if (null !== $pa = $this->reader->getPropertyAnnotation($property, Field::class)) {
                $mapper->addMapping($pa->name ?: $this->namingStrategy->propertyToColumnName($property->getName()), $property->getName());
            }
        }
    }
}
