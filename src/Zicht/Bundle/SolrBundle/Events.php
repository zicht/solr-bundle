<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle;

/**
 * Class Events
 * @package Zicht\Bundle\SolrBundle
 */
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
     * will be dispatched before the request is give to the handler to send
     * and an be used to alter the request.
     *
     * @param \Zicht\Bundle\SolrBundle\Event\HttpClientRequestEvent $event.
     */
    const HTTP_CLIENT_REQUEST = 'http.client_request';

    /**
     * will be dispatched after request is send and can be used to alter
     * or check the response.
     *
     * @param \Zicht\Bundle\SolrBundle\Event\HttpClientResponseEvent $event.
     */
    const HTTP_CLIENT_RESPONSE = 'net.client_response';

    /**
     * this event can be used to add dynamic fields names to the solr
     * document or when a bit more control is needed for the document
     * mapping.
     *
     * @param \Zicht\Bundle\SolrBundle\Event\MapEvent $event.
     */
    const DOCUMENT_MAPPING_PRE = 'solr.document_mapping.pre';

    /**
     * this event is can be used to the same as DOCUMENT_MAPPING_PRE
     * but because it is afgter the mapping it can also be used to
     * alter the dynamic mappings.
     *
     * @param \Zicht\Bundle\SolrBundle\Event\MapEvent $event.
     */
    const DOCUMENT_MAPPING_POST = 'solr.document_mapping.post';

    /**
     * this event will be fired after the `updateEntity` call
     * when an entity is added to the update query and can be
     * used to add extra data to the query builder
     *
     * @param \Zicht\Bundle\SolrBundle\Event\SolrUpdateEvent $event.
     */
    const SOLR_POST_UPDATE = 'solr.post_update';

    /**
     * this event will be fired after the `removeEntity` call
     * when an entity is added to the update query and can be
     * used to add extra data to the query builder
     *
     * @param \Zicht\Bundle\SolrBundle\Event\SolrUpdateEvent $event.
     */
    const SOLR_POST_DELETE = 'solr.post_delete';
}
