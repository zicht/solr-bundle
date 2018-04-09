<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapper;

/**
 * Class DocumentMapperMetadata
 *
 * @package Zicht\Bundle\SolrBundle\Mapper
 */
class DocumentMapperMetadata
{
    /** @var array */
    private $staticFields = [];
    /** @var array */
    private $mapping = [];
    /** @var bool */
    private $strict;
    /** @var string */
    private $className;
    /** @var string */
    private $repository;
    /** @var bool */
    private $active = true;

    /**
     * DocumentMapperMetadata constructor.
     *
     * @param string $className
     * @param null $repository
     * @param bool $strict
     */
    public function __construct($className, $repository = null, $strict = false)
    {
        $this->className = $className;
        $this->repository = $repository;
        $this->strict = $strict;
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
     * @return array
     */
    public function getStaticFields()
    {
        return $this->staticFields;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addStaticFields($name, $value)
    {
        $this->staticFields[$name] = $value;
    }

    /**
     * @param array $staticFields
     */
    public function setStaticFields(array $staticFields)
    {
        $this->staticFields = $staticFields;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param string $name
     * @param string $property
     * @return DocumentMapperMetadata
     */
    public function addMapping($name, $property)
    {
        $this->mapping[$name] = $property;
        return $this;
    }

    /**
     * @param array $mapping
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
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
     * @return bool
     */
    public function isStrict()
    {
        return $this->strict;
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
}
