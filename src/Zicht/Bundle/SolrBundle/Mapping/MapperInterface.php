<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;

interface MapperInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param object $object
     * @param array $data
     * @param ObjectStorage $container
     * @return void
     */
    public function append($object, array &$data, ObjectStorage $container = null);

    /**
     * @inheritdoc
     */
    public function __toString();
}