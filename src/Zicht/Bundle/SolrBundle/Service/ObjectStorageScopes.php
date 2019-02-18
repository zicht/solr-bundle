<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Service;

/**
 * Class ObjectStorageScopes
 * @package Zicht\Bundle\SolrBundle\Service
 */
final class ObjectStorageScopes
{
    /** no need to initialize this class */
    private function __construct()
    {
    }

    /**
     *  used for injecting id generators instances (with dependencies) that are used for
     *  generating document id`s (see @Zicht\Bundle\SolrBundle\Mapping\IdGenerator)
     */
    const SCOPE_MAPPING_MARSHALLER = 'mapping.marshaller';

    /**
     * used for injecting marshaller instances (with dependencies) that are used for
     * marshalling mapped data (see @Zicht\Bundle\SolrBundle\Mapping\Document)
     */
    const SCOPE_MAPPING_REPOSITORY = 'mapping.repository';

    /**
     * used for injecting repository instances (with dependencies) used for the
     * @Zicht\Bundle\SolrBundle\Mapping\Document::repository
     */
    const SCOPE_DOCUMENT_ID_GENERATOR = 'document.id_generator';

    /**
     * used for registering an document listener (see @Zicht\Bundle\SolrBundle\Mapping\DocumentListener)
     */
    const SCOPE_DOCUMENT_LISTENER = 'document.listener';

    /**
     * used for registering document field transformers (see @Zicht\Bundle\SolrBundle\Mapping\TransformInterface)
     */
    const SCOPE_MAPPING_FIELD_TRANSFORMER = 'mapping.field.transformer';

    /**
     * @return array
     */
    public static function getAllScopes()
    {
        return [
            self::SCOPE_MAPPING_MARSHALLER,
            self::SCOPE_MAPPING_REPOSITORY,
            self::SCOPE_DOCUMENT_ID_GENERATOR,
            self::SCOPE_DOCUMENT_LISTENER ,
            self::SCOPE_MAPPING_FIELD_TRANSFORMER ,
        ];
    }
}
