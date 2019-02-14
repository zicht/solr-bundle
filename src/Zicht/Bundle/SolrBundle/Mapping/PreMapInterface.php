<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
declare(strict_types=1);

namespace Zicht\Bundle\SolrBundle\Mapping;

interface PreMapInterface
{
    /**
     * @param object $object
     * @param array $map
     */
    public function preMap($object, array &$map) :void;
}