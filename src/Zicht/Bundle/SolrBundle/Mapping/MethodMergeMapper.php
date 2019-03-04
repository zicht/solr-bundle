<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;

/**
 * Class MethodMergeMapper
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
class MethodMergeMapper implements MapperInterface
{
    use MethodMapperTrait;

    /**
     * MethodMergeMapper constructor.
     * @param $class
     * @param $method
     */
    public function __construct($class, $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * @inheritdoc
     */
    public function append($object, array &$data, ObjectStorage $container = null)
    {
        $this->merge($data, $object->{$this->method}());
    }

    /**
     * do deep merge while preserving keys
     *
     * @param array $a
     * @param array $b
     */
    private function merge(&$a, $b)
    {
        foreach ($a as $key => &$value) {
            if (isset($b[$key])) {
                if (is_array($value)) {
                    $this->merge($value, $b[$key]);
                } else {
                    $a[$key] = $b[$key];
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->__toString();
    }
}