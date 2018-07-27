<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;
use Zicht\Bundle\SolrBundle\Service\ObjectStorageScopes;

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
    public function append(ObjectStorage $container, $entity, array &$data)
    {
        $data[$this->name] = $container->get($this->class, ObjectStorageScopes::SCOPE_MAPPING_MARSHALLER)->{$this->method}($this->resolve($entity));
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return sprintf('%s::%s($%s)', $this->class, $this->method, $this->property);
    }
}
