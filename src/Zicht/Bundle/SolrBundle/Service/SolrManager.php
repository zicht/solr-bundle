<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Service;

use Zicht\Bundle\SolrBundle\Exception\BadMethodCallException;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadataFactory;
use Zicht\Bundle\SolrBundle\Mapping\IdGeneratorDefault;
use Zicht\Bundle\SolrBundle\Mapping\IdGeneratorInterface;
use Zicht\Bundle\SolrBundle\Mapping\PropertyValueTrait;
use Zicht\Bundle\SolrBundle\QueryBuilder\Update;


class SolrManager
{
    use PropertyValueTrait;

    /** @var bool */
    private $enabled = true;
    /** @var SolrClient  */
    private $client;
    /** @var  DocumentMapperMetadataFactory */
    private $documentMetadataFactory;
    /** @var ObjectStorage */
    private $objectStorage;

    /**
     * @param SolrClient $client
     * @param DocumentMapperMetadataFactory $documentMetadataFactory
     * @param ObjectStorage|null $objectStorage
     */
    public function __construct(SolrClient $client, DocumentMapperMetadataFactory $documentMetadataFactory, ObjectStorage $objectStorage = null)
    {
        $this->client = $client;
        $this->objectStorage = $objectStorage ?: new ObjectStorage();
        $this->documentMetadataFactory = $documentMetadataFactory;
    }

    /**
     * @return DocumentMapperMetadataFactory
     */
    public function getDocumentMapperMetadataFactory()
    {
        return $this->documentMetadataFactory;
    }

    /**
     * @param string $className
     * @return DocumentMapperMetadata
     */
    public function getDocumentMapperMetadata($className)
    {
        return $this->documentMetadataFactory->getDocumentMapperMetadataForClass($className);
    }

    /**
     * @param object|string $entity
     * @return null|object
     */
    public function getRepository($entity)
    {
        $meta = $this->getDocumentMapperMetadata($entity);

        if (null !== $repo = $meta->getRepository()) {
            return $this->objectStorage->get($repo, ObjectStorageScopes::SCOPE_MAPPING_REPOSITORY);
        }

        return null;
    }

//    /**
//     * Update an entity from a doctrine change set
//     *
//     * @param object $entity
//     * @param array $changes
//     * @return bool
//     */
//    public function updateFromChangeSet($entity, array $changes)
//    {
////        if (!$this->enabled) {
////            return false;
////        }
////
////        $data = ['id' => $this->documentIdGenerator->generate($entity)];
////        $meta = $this->getDocumentMapperMetadata($entity);
////
////        foreach ($meta->getMapping() as $id => $field) {
////            if (isset($changes[$field->getName()])) {
////                $data[$id] = $changes[$field->getName()][1];
////            }
////        }
////
////        $update = new QueryBuilder\Update();
////        $update->add($data, $meta->getParams());
////        $update->commit();
////
////        $this->client->update($update);
//
//        return true;
//    }

    /**
     * Update an entity
     *
     * @param mixed... $entities
     * @return bool
     */
    public function update(...$entities)
    {
        if (!$this->enabled) {
            return false;
        }
        $update = new Update();
        foreach ($entities as $entity) {
            $meta = $this->getDocumentMapperMetadata($entity);
            if (!$meta->isActive()) {
                continue;
            }
            $update->add($this->map($meta, $entity), $meta->getParams());
        }
        $update->commit();
        $this->client->update($update);
        return true;
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param object $entity
     * @return array
     */
    public function map(DocumentMapperMetadata $meta, $entity)
    {
        $data = ['id' => $this->getDocumentId($meta, $entity)];

        foreach ($meta->getMapping() as $mapping) {
            $mapping->append($this->objectStorage, $entity, $data);
        }

        return $data;
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param object $entity
     * @return string
     */
    private function getDocumentId(DocumentMapperMetadata $meta, $entity)
    {
        if (null === $generator = $meta->getIdGenerator()) {
            $generator = IdGeneratorDefault::class;
        }

        return $this->objectStorage->get($generator, ObjectStorageScopes::SCOPE_DOCUMENT_ID_GENERATOR)->generate($entity);
    }


    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return SolrClient
     */
    public function getClient()
    {
        return $this->client;
    }
}