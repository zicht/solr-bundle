<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Service;

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
     * marshalling mapped data (see @Zicht\Bundle\SolrBundle\Mapping\Marshaller)
     */
    const SCOPE_MAPPING_REPOSITORY = 'mapping.repository';

    /**
     * used for injecting repository instances (with dependencies) used for the
     * @Zicht\Bundle\SolrBundle\Mapping\Document::repository
     */
    const SCOPE_DOCUMENT_ID_GENERATOR = 'document.id_generator';

    /**
     * @return array
     */
    public static function getAllScopes()
    {
        return [
            self::SCOPE_MAPPING_MARSHALLER,
            self::SCOPE_MAPPING_REPOSITORY,
            self::SCOPE_DOCUMENT_ID_GENERATOR ,
        ];
    }
}

