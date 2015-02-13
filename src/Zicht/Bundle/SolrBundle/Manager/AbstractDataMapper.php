<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use \Solarium\Client;
use \Solarium\QueryType\Update\Query\Document;

/**
 * Class DataMapper
 * @package Zicht\Bundle\SolrBundle\Manager
 */
abstract class AbstractDataMapper implements DataMapperInterface
{
    protected $classNames = array();

    /**
     * Update the specified entity
     *
     * @param Client $client
     * @param mixed $entity
     * @return void
     */
    public function update(Client $client, $entity)
    {
        $update = $client->createUpdate();
        $this->addUpdateDocument($update, $entity);
        $update->addCommit();
        $client->update($update);
    }


    /**
     * Delete the specified entity from the database
     *
     * @param Client $client
     * @param mixed $entity
     * @return void
     */
    public function delete(Client $client, $entity)
    {
        $update = $client->createUpdate();
        $this->addDeleteDocument($update, $entity);
        $update->addCommit();
        $client->update($update);
    }


    /**
     * Extract a document.
     *
     * @param Client $client
     * @param mixed $entity
     * @param string $filePath
     */
    public function extract(Client $client, $entity, $filePath)
    {
        $query = $client->createExtract();
        $document = $query->createDocument();

        if (($boost = $this->getBoost($entity))) {
            $document->setBoost($boost);
        }
        $document->addField('id', $this->generateObjectIdentity($entity));
        $this->mapDocument($entity, $document);

        $query->setDocument($document);
        $query->setFile($filePath);
        $query->setCommit(true);

        $client->execute($query);
    }


    /**
     * Maps the data to an indexable document for Solr
     *
     * @param \Solarium\QueryType\Update\Query\Query $updateQuery
     * @param mixed $entity
     * @return void
     */
    public function addUpdateDocument($updateQuery, $entity)
    {
        $document = $updateQuery->createDocument();

        if (($boost = $this->getBoost($entity))) {
            $document->setBoost($boost);
        }
        $document->addField('id', $this->generateObjectIdentity($entity));
        $this->mapDocument($entity, $document);
        $updateQuery->addDocument($document);
    }

    /**
     * @param \Solarium\QueryType\Update\Query\Query $updateQuery
     * @param mixed $entity
     * @return mixed
     */
    public function addDeleteDocument($updateQuery, $entity)
    {
        $updateQuery->addDeleteQuery('id:' . $this->generateObjectIdentity($entity));
    }


    /**
     * @param mixed $entity
     * @return float
     */
    protected function getBoost($entity)
    {
        return 0.0;
    }

    /**
     * Return an object id.
     *
     * @param mixed $entity
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    protected function generateObjectIdentity($entity)
    {
        if (method_exists($entity, 'getId')) {
            return sha1(get_class($entity) . ':' . $entity->getId());
        }

        $me = get_class($this);
        $className = get_class($entity);

        throw new \UnexpectedValueException("$className has no getId() method. Either implement it, or override $me::generateObjectIdentity()");
    }

    /**
     * @param mixed $entity
     * @return mixed
     */
    public function supports($entity)
    {
        foreach ($this->classNames as $name) {
            if ($entity instanceof $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $classNames
     */
    public function setClassNames($classNames)
    {
        $this->classNames = $classNames;
    }

    /**
     * Map document data
     *
     * @param mixed $entity
     * @param Document $document
     * @return mixed
     */
    abstract protected function mapDocument($entity, Document $document);
}