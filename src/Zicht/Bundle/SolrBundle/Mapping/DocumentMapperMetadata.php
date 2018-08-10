<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Exception\InvalidArgumentException;

/**
 * Class DocumentMapperMetadata
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
class DocumentMapperMetadata
{
    /** @var array|MapperInterface[] */
    private $mapping = [];
    /** @var array */
    private $options;
    /** @var string */
    private $className;
    /** @var string */
    private $repository;
    /** @var bool */
    private $active = true;
    /** @var array */
    private $params = [];
    /** @var string[][] */
    private $transformers = [];
    /** @var array */
    private $idField;
    /** @var string|null */
    private $idGenerator;

    /**
     * DocumentMapperMetadata constructor.
     *
     * @param string $className
     * @param null|string $repository
     * @param array $options
     */
    public function __construct($className, $repository = null, array $options = [])
    {
        $this->className = $className;
        $this->options = $options;
        $this->setRepository($repository);
    }

    /**
     * @param string $className
     * @return DocumentMapperMetadata
     */
    public function newWith($className)
    {
        $instance = clone $this;
        $instance->className = $className;
        return $instance;
    }

    /**
     * @return array|MapperInterface[]
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param MapperInterface $mapping
     * @return $this
     */
    public function addMapping(MapperInterface $mapping)
    {
        $this->mapping[] = $mapping;
        return $this;
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function hasMapping($name)
    {
        foreach ($this->mapping as $mapping) {
            if ($name === $mapping->getName()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array|MapperInterface[] $mappings
     */
    public function setMapping(array $mappings)
    {
        foreach ($mappings as $mapping) {
            $this->addMapping($mapping);
        }
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param $repository
     */
    public function setRepository($repository)
    {
        if (is_a($repository, DocumentRepositoryInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('Expected "%s" to implement "%s"', $repository, DocumentRepositoryInterface::class));
        }

        $this->repository = $repository;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return DocumentMapperMetadata
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getIdField()
    {
        return $this->idField;
    }

    /**
     * @param string $scope
     * @param string $ref
     * @return $this
     */
    public function setIdField($scope, $ref)
    {
        $this->idField = [$scope, $ref];
        return $this;
    }

    /**
     * @return null|string
     */
    public function getIdGenerator()
    {
        return empty($this->idGenerator) ? null : $this->idGenerator;
    }

    /**
     * @param null|string $idGenerator
     */
    public function setIdGenerator($idGenerator)
    {
        if (!is_a($idGenerator, IdGeneratorInterface::class)) {
            throw new InvalidArgumentException(sprintf('Expected "%s" to implement "%s"', $idGenerator, IdGeneratorInterface::class));
        }

        $this->idGenerator = $idGenerator;
    }

    /**
     * @param null|string $name
     * @return \Generator
     */
    public function getTransformers($name = null)
    {
        foreach ($this->transformers as $group) {
            foreach ($group as $property => $className) {
                if (is_null($name) || $name === $property) {
                    yield $property => $className;
                }
            }
        }
    }

    /**
     * @param string[] $transformers
     */
    public function setTransformers($transformers)
    {
        $this->transformers = [];

        foreach ($transformers as $name => $transformer) {
            foreach ((array)$transformer as $c) {
                $this->addTransformer($name, $c);
            }
        }
    }

    /**
     * @param string $name
     * @param string $transformer
     * @param int $weight
     */
    public function addTransformer($name, $transformer, $weight = 0)
    {
        if (!is_a($transformer, TransformInterface::class)) {
            throw new InvalidArgumentException(sprintf('Expected "%s" to implement "%s"', $transformer, TransformInterface::class));
        }

        $dirty = !isset($this->transformers[$weight]);

        $this->transformers[$weight][$name][] = $transformer;

        if ($dirty) {
            krsort($this->transformers);
        }
    }
}
