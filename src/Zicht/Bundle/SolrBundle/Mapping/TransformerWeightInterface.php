<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

/**
 * Interface TransformerWeightInterface
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
interface TransformerWeightInterface
{
    /**
     * @return int
     */
    public function getWeight();
}
