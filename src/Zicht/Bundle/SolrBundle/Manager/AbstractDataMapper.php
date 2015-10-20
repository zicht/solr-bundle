<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use \Solarium\Client;
use \Solarium\QueryType\Update\Query\Document\Document;


/**
 * Class DataMapper
 * @package Zicht\Bundle\SolrBundle\Manager
 */
abstract class AbstractDataMapper implements DataMapperInterface
{
    const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    protected $classNames = array();

    /**
     * Format date for SOLR
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    static function formatDate($dateTime)
    {
        if (null === $dateTime) {
            return null;
        }
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        return $dateTime->format(self::DATE_FORMAT);
    }

    /**
     * Update the specified entity
     *
     * @param Client $client
     * @param mixed $entity
     * @return void
     */
    public function update(Client $client, $entity, $batch = null)
    {
        if (null !== $batch) {
            $this->addUpdateDocument($batch, $entity);
        } else {
            $update = $client->createUpdate();
            $this->addUpdateDocument($update, $entity);
            $update->addCommit();
            $client->update($update);
        }
    }


    /**
     * Delete the specified entity from the database
     *
     * @param Client $client
     * @param mixed $entity
     * @return void
     */
    public function delete(Client $client, $entity, $batch = null)
    {
        if (null !== $batch) {
            $this->addDeleteDocument($batch, $entity);
        } else {
            $update = $client->createUpdate();
            $this->addDeleteDocument($update, $entity);
            $update->addCommit();
            $client->update($update);
        }
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
