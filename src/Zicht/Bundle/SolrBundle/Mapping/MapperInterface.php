<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Solr\ObjectStorage;

interface MapperInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param ObjectStorage $container
     * @param object $entity
     * @param array $data
     * @return void
     */
    public function append(ObjectStorage $container, $entity, array &$data);

    /**
     * @inheritdoc
     */
    public function __toString();
}