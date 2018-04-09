<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Zicht\Bundle\SolrBundle\Mapper\DocumentMapperMetadata;

class DocumentMapperMetadataEvent extends Event
{
    /** @var DocumentMapperMetadata  */
    private $metadata;

    /**
     * DocumentMetadataEvent constructor.
     *
     * @param DocumentMapperMetadata $mapper
     */
    public function __construct(DocumentMapperMetadata $mapper)
    {
        $this->metadata = $mapper;
    }

    /**
     * @return DocumentMapperMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}