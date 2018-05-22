<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

/**
 * Interface DocumentIdGeneratorInterface
 *
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
interface IdGeneratorInterface
{
    /**
     * @param object $object
     * @return string
     */
    public function generate($object);
}
