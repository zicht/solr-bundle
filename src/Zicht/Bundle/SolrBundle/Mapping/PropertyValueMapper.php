<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;

/**
 * Class PropertyValueMapper
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
class PropertyValueMapper extends AbstractMapper
{
    use PropertyValueTrait;

    /** @var string */
    protected $scope;
    /** @var string */
    protected $property;

    /**
     * @param string $name
     * @param string $scope
     * @param string $property
     */
    public function __construct($name, $scope, $property)
    {
        parent::__construct($name);
        $this->scope = $scope;
        $this->property= $property;
    }

    /**
     * @inheritdoc
     */
    public function append($object, array &$data, ObjectStorage $container = null)
    {
        $data[$this->name] = $this->resolve($object);
    }

    /**
     * @param object $entity
     * @return mixed
     */
    protected function resolve($entity)
    {
        return $this->resolveProperty($entity, $this->scope, $this->property);
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return sprintf('%s::$%s', $this->scope, $this->property);
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }
}