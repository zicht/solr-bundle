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
     * @param \Zicht\Bundle\SolrBundle\Event\MetadataLoadDocumentMapperEvent $event.
     */
    const METADATA_LOAD_DOCUMENT_MAPPER = 'solr.metadata_load_document_mapper';

    /**
     * this event will be called when the factory class has build a list
     * of supported entities and can be used to alter the support list.
     *
     * @param \Zicht\Bundle\SolrBundle\Event\MetadataPostBuildEntitiesListEvent $event.
     */
    const METADATA_POST_BUILD_ENTITIES_LIST = 'solr.metadata_post_build_entities_list';

    /**
     * @param \Zicht\Bundle\SolrBundle\Event\MetadataPostBuildEntitiesListEvent $event.
     */
    const NET_CLIENT_PRE_SEND_REQUEST = 'net.client_pre_send_request';

    /**
     * @param \Zicht\Bundle\SolrBundle\Event\MetadataPostBuildEntitiesListEvent $event.
     */
    const NET_CLIENT_POST_SEND_REQUEST = 'net.client_post_send_request';
}