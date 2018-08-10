<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

/**
 * Interface TransformInterface
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
interface TransformInterface
{
    /**
     * @param mixed $data
     * @return mixed
     */
    public function __invoke($data);
}
