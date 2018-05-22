<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Solr;

use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadataFactory;
use Zicht\Bundle\SolrBundle\Mapping\IdGeneratorDefault;
use Zicht\Bundle\SolrBundle\Mapping\IdGeneratorInterface;
use Zicht\Bundle\SolrBundle\Mapping\PropertyValueTrait;

class SolrManager
{
    use PropertyValueTrait;

    /** @var bool */
    private $enabled = true;
    /** @var Client  */
    private $client;
    /** @var  DocumentMapperMetadataFactory */
    private $documentMetadataFactory;
    /** @var ObjectStorage */
    private $objectStorage;

    /**
     * SolrManager constructor.
     * @param Client $client
     * @param DocumentMapperMetadataFactory $documentMetadataFactory
     * @param ObjectStorage|null $objectStorage
     */
    public function __construct(Client $client, DocumentMapperMetadataFactory $documentMetadataFactory, ObjectStorage $objectStorage = null)
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
            return $this->objectStorage->get($repo, 'mapping.repository');
        }

        return null;
    }

    /**
     * Update an entity from a doctrine change set
     *
     * @param object $entity
     * @param array $changes
     * @return bool
     */
    public function updateFromChangeSet($entity, array $changes)
    {
//        if (!$this->enabled) {
//            return false;
//        }
//
//        $data = ['id' => $this->documentIdGenerator->generate($entity)];
//        $meta = $this->getDocumentMapperMetadata($entity);
//
//        foreach ($meta->getMapping() as $id => $field) {
//            if (isset($changes[$field->getName()])) {
//                $data[$id] = $changes[$field->getName()][1];
//            }
//        }
//
//        $update = new QueryBuilder\Update();
//        $update->add($data, $meta->getParams());
//        $update->commit();
//
//        $this->client->update($update);

        return true;
    }

    /**
     * Update an entity
     *
     * @param mixed $entity
     * @return bool
     */
    public function update($entity)
    {
        if (!$this->enabled) {
            return false;
        }

        $meta = $this->getDocumentMapperMetadata($entity);

        if (!$meta->isActive()) {
            return false;
        }

        $update = new QueryBuilder\Update();
        $update->add($this->map($meta, $entity), $meta->getParams());
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

        foreach ($meta->getMapping() as $name => list($type, $scope, $ref, $ctx)) {
            switch (true) {
                case $meta->isMapping($meta::MAPPING_STATIC, $type):
                    if ($meta->isMapping($meta::MAPPING_METHOD, $type)) {
                        $data[$name] = $this->getMarshaller($scope)->{$ref}($entity);
                    } else {
                        $data[$name] = $ref;
                    }
                    break;
                case $meta->isMapping($meta::MAPPING_PROPERTY, $type):
                    $data[$name] = $this->resolveProperty($entity, $scope, $ref);
                    break;
                case $meta->isMapping($meta::MAPPING_METHOD, $type):
                    $data[$name] = $this->getMarshaller($ctx[0])->{$ctx[1]}($this->resolveProperty($entity, $scope, $ref));
                    break;
            }

        }

        return $data;
    }


    /**
     * @param string $className
     * @return object
     */
    private function getMarshaller($className)
    {
        return $this->objectStorage->get($className, 'mapping.marshaller');
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param object $entity
     * @return string
     */
    private function getDocumentId(DocumentMapperMetadata $meta, $entity)
    {
        return $this->getIdGenerator($meta)->generate($entity);
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @return IdGeneratorInterface|object
     */
    private function getIdGenerator(DocumentMapperMetadata $meta)
    {
        $className = $meta->getIdGenerator();

        if (empty($className) || !is_a($className, IdGeneratorInterface::class, true)) {
            $className = IdGeneratorDefault::class;
        }

        return $this->objectStorage->get($className, 'document.id_generator');
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
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}