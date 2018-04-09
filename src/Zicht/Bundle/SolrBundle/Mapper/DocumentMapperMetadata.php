<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapper;

class DocumentMetadata
{
    /** @var array */
    private $staticFields = [];
    /** @var array */
    private $mapping = [];
    /** @var bool */
    private $strict;
    /** @var string */
    private $class;
    /** @var string */
    private $repository;

    /**
     * DocumentMapper constructor.
     *
     * @param string $class
     * @param null $repository
     * @param bool $strict
     */
    public function __construct($class, $repository = null, $strict = false)
    {
        $this->class = $class;
        $this->repository = $repository;
        $this->strict = $strict;
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
     * @return $this
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
    public function getClass()
    {
        return $this->class;
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
}