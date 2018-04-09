<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle;

final class Events
{
    /** This class should not be instantiated */
    private function __construct()
    {
    }

    /**
     * This event will be called when a document mapper metadata is build
     * and can be used to add or alter mapping fields.
     *
     * @param \Zicht\Bundle\SolrBundle\Event\DocumentMapperMetadataEvent $event.
     */
    const LOAD_DOCUMENT_MAPPER_METADATA = 'solr.load_document_mapper_metadata';

    /**
     * this event will be called when the factory class has build a list
     * of supported entities and can be used to alter the support list.
     *
     * @param \Zicht\Bundle\SolrBundle\Event\PostBuildEntitiesListEvent $event.
     */
    const POST_BUILD_ENTITIES_LIST = 'solr.post_build_entities_list';
}