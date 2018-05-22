<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

/**
 * Trait PropertyValueTrait
 *
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
trait PropertyValueTrait
{
    /**
     * @param object $object
     * @param object|string $scope
     * @param string $property
     * @return mixed
     */
    protected function resolveProperty($object, $scope, $property)
    {
        /** @var \Closure $c */
        static $c;

        if (!$c) {
            $c = function ($property) {
                return $this->{$property};
            };
        }

        return $c->bindTo($object, $scope)->__invoke($property);
    }
}
