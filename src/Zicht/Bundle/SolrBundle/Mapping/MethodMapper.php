<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;

/**
 * Class MethodMapper
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
class MethodMapper extends AbstractMapper
{
    use MethodMapperTrait;

    /**
     * @param string $name
     * @param string $class
     * @param string $method
     */
    public function __construct($name, $class, $method)
    {
        parent::__construct($name);
        $this->class = $class;
        $this->method = $method;
    }


    /**
     * @inheritdoc
     */
    public function append($object, array &$data, ObjectStorage $container = null)
    {
        $data[$this->name] = $object->{$this->method}();
    }
}
