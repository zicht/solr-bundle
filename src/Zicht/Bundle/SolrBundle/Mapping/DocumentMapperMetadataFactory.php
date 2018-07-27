<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zicht\Bundle\SolrBundle\Event\MetadataLoadDocumentMapperEvent;
use Zicht\Bundle\SolrBundle\Event\MetadataPostBuildEntitiesListEvent;
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
    public function __construct(NamingStrategy $namingStrategy, CacheInterface $cache, Reader $reader, EventDispatcherInterface $dispatcher, MappingDriver ...$mappings)
    {
        if (null === $entities = $cache->get($this->getCacheKey())) {
            $entities = $this->getEntityInheritanceList($dispatcher, $reader, ...$mappings);
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
     * @param EventDispatcherInterface $dispatcher
     * @param Reader $reader
     * @param MappingDriver[] ...$mappings
     * @return array
     */
    private function getEntityInheritanceList(EventDispatcherInterface $dispatcher, Reader $reader, ...$mappings)
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
                            if (null === $reader->getClassAnnotation(new \ReflectionClass($className), NoDocument::class)) {
                                $entities[$entity][] = $className;
                            }
                        }
                    }
                }
            }
        }
        // remove inherited children that have document annotation
        foreach (array_keys($entities) as $entity) {
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

        // fix indexes
        foreach ($entities as $parent => $children) {
            $entities[$parent] = array_values($children);
        }

        if ($dispatcher->hasListeners(Events::METADATA_POST_BUILD_ENTITIES_LIST)) {
            $entities = $dispatcher->dispatch(Events::METADATA_POST_BUILD_ENTITIES_LIST, new MetadataPostBuildEntitiesListEvent($entities))->getList();
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
        if (is_object($className)) {
            $className = get_class($className);
        }
        if (false === $this->support($className)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a solr mapped entity', $className));
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
                $this->readDocumentIdGenerator($reflection, $metadata);
                $this->readParams($reflection, $metadata);
                $this->readStaticFields($reflection, $metadata);
                $this->readProperties($reflection, $metadata);
                $this->readMethods($reflection, $metadata);
            } while ($reflection = $reflection->getParentClass());

            if ($this->eventDispatcher->hasListeners(Events::METADATA_LOAD_DOCUMENT_MAPPER)) {
                $this->eventDispatcher->dispatch(Events::METADATA_LOAD_DOCUMENT_MAPPER, new MetadataLoadDocumentMapperEvent($metadata));
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
        return new DocumentMapperMetadata($reflection->getName(), $annotation->repository, ['strict' => $annotation->strict, 'transformers' => $annotation->transformers]);
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param DocumentMapperMetadata $mapper
     */
    protected function readDocumentIdGenerator(\ReflectionClass $reflectionClass, DocumentMapperMetadata $mapper)
    {
        /** @var IdGenerator $annotations */
        if (null !== $annotations = $this->reader->getClassAnnotation($reflectionClass, IdGenerator::class)) {
            $mapper->setIdGenerator($annotations->value);
        }
    }

            /**
     * @param \ReflectionClass $reflectionClass
     * @param DocumentMapperMetadata $mapper
     */
    protected function readParams(\ReflectionClass $reflectionClass, DocumentMapperMetadata $mapper)
    {
        /** @var Params $annotations */
        if (null !== $annotations = $this->reader->getClassAnnotation($reflectionClass, Params::class)) {
            $mapper->setParams(array_merge($annotations->value, $mapper->getParams()));
        }
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param DocumentMapperMetadata $mapper
     */
    protected function readStaticFields(\ReflectionClass $reflectionClass, DocumentMapperMetadata $mapper)
    {
        /** @var Fields $annotations */
        if (null !== $annotations = $this->reader->getClassAnnotation($reflectionClass, Fields::class)) {
            foreach ((array)$annotations->value as $name => $value) {
                if ($mapper->hasMapping($name)) {
                    continue;
                }
                if ($value instanceof Marshaller) {
                    $mapper->addMapping(new StaticMethodMapper($name, $value->className, $this->getMethodName($value, $name)));
                } else {
                    $mapper->addMapping(new StaticValueMapper($name, $value));
                }
            }
        }
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param DocumentMapperMetadata $mapper
     */
    protected function readMethods(\ReflectionClass $reflectionClass, DocumentMapperMetadata $mapper)
    {
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (null !== $annotation = $this->reader->getMethodAnnotation($method, Field::class)) {
                $mapper->addMapping(new MethodMapper(
                    $annotation->name ?: $this->namingStrategy->propertyToColumnName($method->name),
                    $method->class,
                    $method->name
                ));
            }
            if (null !== $annotation = $this->reader->getMethodAnnotation($method, Fields::class)) {
                $mapper->addMapping(new MethodMergeMapper(
                    $this->namingStrategy->propertyToColumnName($method->name),
                    $method->class,
                    $method->name
                ));
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
            if (null !== $this->reader->getPropertyAnnotation($property, Id::class)) {
                $mapper->setIdField($property->class, $property->name);
            }
            /** @var Field $annotation */
            if (null !== $annotation = $this->reader->getPropertyAnnotation($property, Field::class)) {

                $name = $annotation->name ?: $this->namingStrategy->propertyToColumnName($property->name);

                /** @var Marshaller $marshaller  */
                if (null !== $marshaller = $this->reader->getPropertyAnnotation($property, Marshaller::class)) {
                    $mapper->addMapping(new PropertyMethodMapper(
                        $name,
                        $property->class,
                        $property->name,
                        $marshaller->className,
                        $this->getMethodName($marshaller , $property->name)
                    ));
                } else {
                    $mapper->addMapping(new PropertyValueMapper($name, $property->class, $property->name));
                }

                foreach ($this->reader->getPropertyAnnotations($property) as $annotation) {
                    if ($annotation instanceof TransformInterface) {
                        $mapper->addTransformer($property->name, $annotation);
                    }
                    if ($annotation instanceof Column) {
                        foreach ($mapper->getOption('transformers', []) as $transformer => $pattern) {
                            if (preg_match($pattern, $annotation->type)) {
                                $mapper->addTransformer($name, $transformer);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Marshaller $marshaller
     * @param string $name
     * @return string
     */
    protected function getMethodName(Marshaller $marshaller, $name)
    {
        if (!empty($marshaller->method)) {
            return $marshaller->method;
        }

        return 'from' . ucfirst(str_replace(['-', '_', '.'], '', ucwords($name, '-_.')));
    }

    /**
     * @return CacheInterface
     */
    public function getCacheImpl()
    {
        return $this->cache;
    }
}
