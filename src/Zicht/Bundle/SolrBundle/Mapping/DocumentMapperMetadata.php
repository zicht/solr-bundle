<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

/**
 * Class DocumentMapperMetadata
 *
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
class DocumentMapperMetadata
{
    const MAPPING_PROPERTY = 0x01;
    const MAPPING_METHOD = 0x02;
    const MAPPING_STATIC = 0x04;

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
    /** @var array */
    private $exclude;
    /** @var array */
    private $params = [];
    /** @var array */
    private $idField;
    /** @var string|null */
    private $idGenerator;

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
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param int $type
     * @param string $name
     * @param string $ref
     * @param string|null $scope
     * @return $this
     */
    public function addMapping($type, $name, $ref, $scope = null, array $ctx = [])
    {
        $this->mapping[$name] = [$type, $scope, $ref, $ctx];
        return $this;
    }

    /**
     * @param array $mapping
     */
    public function setMapping(array $mapping)
    {
        foreach ($mapping as $name => $data) {
            $this->addMapping($name, ...$data);
        }
    }

    public function isMapping($expected, $got)
    {
        return $expected === ($expected & $got);

    }

    /**
     * @param string $name
     * @return bool
     */
    public function isMethodMapping($name)
    {
        if (isset($this->mapping[$name])) {
            return $this->isMapping(self::MAPPING_METHOD, $this->mapping[$name][0]);
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isPropertyMapping($name)
    {
        if (isset($this->mapping[$name])) {
            return $this->isMapping(self::MAPPING_PROPERTY, $this->mapping[$name][0]);
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isStaticMapping($name)
    {
        if (isset($this->mapping[$name])) {
            return $this->isMapping(self::MAPPING_STATIC, $this->mapping[$name][0]);
        }

        return false;
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

    /**
     * @return array
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * @param array $exclude
     * @return DocumentMapperMetadata
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;
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
        return $this->idGenerator;
    }

    /**
     * @param null|string $idGenerator
     */
    public function setIdGenerator($idGenerator)
    {
        $this->idGenerator = $idGenerator;
    }
}
