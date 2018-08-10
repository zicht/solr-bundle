<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

/**
 * Class IdGeneratorDefault
 *
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
class IdGeneratorDefault implements IdGeneratorInterface
{
    use PropertyValueTrait;

    /** @var DocumentMapperMetadataFactory */
    private $metadataFactory;

    /**
     * IdGeneratorDefault constructor.
     *
     * @param DocumentMapperMetadataFactory $metadataFactory
     */
    public function __construct(DocumentMapperMetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @inheritdoc
     */
    public function generate($object)
    {
        $meta = $this->metadataFactory->getDocumentMapperMetadataForClass($object);
        return sha1($meta->getClassName() . ':' . $this->resolveProperty($object, ...$meta->getIdField()));
    }
}
