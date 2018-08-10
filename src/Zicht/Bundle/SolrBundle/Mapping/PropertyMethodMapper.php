<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;
use Zicht\Bundle\SolrBundle\Service\ObjectStorageScopes;

/**
 * Class PropertyMethodMapper
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
class PropertyMethodMapper extends PropertyValueMapper
{
    /** @var string  */
    protected $class;
    /** @var string  */
    protected $method;

    /**
     * @param string $name
     * @param string $scope
     * @param string $property
     * @param string $class
     * @param string $method
     */
    public function __construct($name, $scope, $property, $class, $method)
    {
        parent::__construct($name, $scope, $property);
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * @inheritdoc
     */
    public function append($object, array &$data, ObjectStorage $container = null)
    {

        if (is_null($container)) {
            $class = new $this->class();
        } else {
            $class = $container->get($this->class, ObjectStorageScopes::SCOPE_MAPPING_MARSHALLER);
        }

        $data[$this->name] = $class->{$this->method}($this->resolve($object));
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return sprintf('%s::%s($%s)', $this->class, $this->method, $this->property);
    }
}
