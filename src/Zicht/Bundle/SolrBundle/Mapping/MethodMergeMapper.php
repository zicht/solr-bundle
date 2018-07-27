<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;

class MethodMergeMapper extends MethodMapper
{
    /**
     * @inheritdoc
     */
    public function append(ObjectStorage $container, $entity, array &$data)
    {
        foreach ($entity->{$this->method} as $key => $value) {
            if (!isset($data[$key])) {
                $data[$key] = $value;
            }
        }
    }
}