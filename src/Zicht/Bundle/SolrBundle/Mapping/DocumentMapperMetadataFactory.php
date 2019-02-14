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
 * @package Zicht\Bundle\SolrBundle\Pager
 */
class DocumentMapperMetadataFactory
{
    /** @var string */
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
     * class) when the child_inheritance annotation is set to false.
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
                $entry = [
                    'className' => $entity,
                    'children' => []
                ];
                // search for any child classes where this class is the parent of
                if ($annotation->child_inheritance) {
                    foreach ($allEntities as $className) {
                        if ($className === $entity) {
                            continue;
                        }
                        if (is_a($className, $entity, true)) {
                            if (null === $reader->getClassAnnotation(new \ReflectionClass($className), NoDocument::class)) {
                                $entry['children'][] = $className;
                            }
                        }
                    }
                }
                $entities[] = $entry;
            }
        }

        // remove inherited children that have document annotation
        foreach (array_column($entities, 'className') as $entityIndex => $entity) {
            foreach ($entities as $entityMapIndex => $entityMap) {
                if ($entityMap['className'] === $entity) {
                    continue;
                }

                if (false !== $index = array_search($entity, $entityMap['children'])) {
                    // remove from child list because it is managed by it self
                    unset($entities[$entityMapIndex]['children'][$index]);

                    // remove children of the $entityMap that ar also instance of and
                    // managed by $entity. So in other words remove children that are
                    // also instance of parent because the extend the child.
                    foreach ($entities[$entityIndex]['children'] as $index => $name) {
                        if (in_array($name, $entityMap['children'])) {
                            unset($entities[$entityMapIndex]['children'][$index]);
                        }
                    }
                }
            }
        }

        if ($dispatcher->hasListeners(Events::METADATA_POST_BUILD_ENTITIES_LIST)) {
            $entities = $dispatcher->dispatch(Events::METADATA_POST_BUILD_ENTITIES_LIST, new MetadataPostBuildEntitiesListEvent($entities))->getList();
        }

        // fix indexes
        $entities = array_values($entities);

        foreach ($entities as $index => $map) {
            $entities[$index]['children'] = array_values($map['children']);
        }

        return $entities;
    }

    /**
     * @param bool $noChildren
     * @return \Generator|string[]
     */
    public function getEntities($noChildren = false)
    {
        foreach ($this->entities as $map) {
            yield $map['className'];

            if (!$noChildren) {
                foreach ($map['children'] as $child) {
                    yield $child;
                }
            }
        }
    }

    /**
     * @return array|null
     */
    public function getChildrenOf($className)
    {
        if (false !== $index = array_search($className, array_column($this->entities, 'className'))) {
            return !empty($this->entities[$index]['children']) ? $this->entities[$index]['children'] : null;
        }

        return null;
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

        foreach ($this->entities as $map) {
            if ($map['className'] === $className || in_array($className, $map['children'])) {
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

            if (false !== array_search($className, array_column($this->entities, 'className'))) {
                $metadata = $this->newDocumentMetadata($reflection);
            } else {
                // resolve from parent
                foreach ($this->entities as $map) {
                    if (in_array($className, $map['children'])) {
                        $metadata = $this->getDocumentMapperMetadataForClass($map['className'])->newWith($className);
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
        return sprintf('%s[metadata][%s]', self::CACHE_SUFFIX, str_replace('\\', '', $className));
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
        $args = [$reflection->name];

        /** @var Document $annotation */
        if (null !== $annotation = $this->reader->getClassAnnotation($reflection, Document::class)) {
            $args[] = $annotation->repository;
            $args[] =  [
                'child_inheritance' => $annotation->child_inheritance,
                'transformers' => array_map(
                    function ($value) {
                        return (is_array($value)) ? $value : [0, $value];
                    },
                    $annotation->transformers
                )
            ];
        }
        /** @var DocumentListener $annotation */
        if (null !== $annotation = $this->reader->getClassAnnotation($reflection, DocumentListener::class)) {
            foreach ((array)$annotation->value as $class) {
                if (is_a($class, PreMapInterface::class, true)) {
                    $args[2]['events']['pre_map'][] = $class;
                }
                if (is_a($class, PostMapInterface::class, true)) {
                    $args[2]['events']['post_map'][] = $class;
                }
            }
        }
        return new DocumentMapperMetadata(...$args);
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
                $mapper->addMapping(new MethodMapper($this->getName($annotation->name, $method->name), $method->class, $method->name));
            }

            if (null !== $annotation = $this->reader->getMethodAnnotation($method, Fields::class)) {
                $mapper->addMapping(new MethodMergeMapper($method->class, $method->name));
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
            // check for the doctrine id field, which will be used for generating an document id
            if (null !== $this->reader->getPropertyAnnotation($property, Id::class)) {
                $mapper->setIdField($property->class, $property->name);
            }

            /** @var Field $annotation */
            if (null !== $annotation = $this->reader->getPropertyAnnotation($property, Field::class)) {
                $name = $this->getName($annotation->name, $property->name);

                /**
                 * properties with the field annotation can also have an Marshaller
                 * annotation to define and property value marshaller. Similar as
                 * on the fields annotation except this will only get the property
                 * value as argument.
                 *
                 * @var Marshaller $marshaller
                 */
                if (null !== $marshaller = $this->reader->getPropertyAnnotation($property, Marshaller::class)) {
                    $mapper->addMapping(
                        new PropertyMethodMapper(
                            $name,
                            $property->class,
                            $property->name,
                            $marshaller->className,
                            $this->getMethodName($marshaller, $property->name)
                        )
                    );
                } else {
                    $mapper->addMapping(new PropertyValueMapper($name, $property->class, $property->name));
                }

                /**
                 * Check for transformer annotations on an field, this can be
                 * global defined and match column type or an annotation that
                 * also implements the TransformInterface.
                 */
                foreach ($this->reader->getPropertyAnnotations($property) as $annotation) {
                    if ($annotation instanceof TransformInterface) {
                        if ($annotation instanceof TransformerWeightInterface) {
                            $mapper->addTransformer($property->name, $annotation, $annotation->getWeight());
                        } else {
                            $mapper->addTransformer($property->name, $annotation);
                        }
                    }

                    if ($annotation instanceof Column) {
                        foreach ($mapper->getOption('transformers', []) as $transformer => list($weight, $pattern)) {
                            if (preg_match($pattern, $annotation->type)) {
                                $mapper->addTransformer($name, $transformer, $weight);
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
     *
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
     * @param string $annotationName
     * @param string $propertyName
     *
     * @return string
     */
    protected function getName($annotationName, $propertyName)
    {
        return $annotationName ?: $this->namingStrategy->propertyToColumnName($propertyName);
    }

    /**
     * @return CacheInterface
     */
    public function getCacheImpl()
    {
        return $this->cache;
    }
}
