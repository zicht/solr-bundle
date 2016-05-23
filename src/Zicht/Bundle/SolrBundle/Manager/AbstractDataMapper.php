<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

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
        // force the timezone to be set to UTC, but DON'T mutate the object.
        $cloned = clone $dateTime;
        $cloned->setTimezone(new \DateTimeZone('UTC'));
        return $cloned->format(self::DATE_FORMAT);
    }

    /**
     * Update the specified entity
     *
     * @param Client $client
     * @param mixed $entity
     * @return void
     */
    public function update(Update $update, $entity, $batch = null)
    {
        $this->addUpdateDocument($update, $entity);
    }


    /**
     * Delete the specified entity from the database
     *
     * @param Client $client
     * @param mixed $entity
     * @return void
     */
    public function delete(Update $update, $entity, $batch = null)
    {
        $update->deleteOne($this->generateObjectIdentity($entity));
    }


    /**
     * Maps the data to an indexable document for Solr
     *
     * @param mixed $entity
     * @return void
     */
    public function addUpdateDocument(Update $updateQuery, $entity)
    {
        $params = [];
        if (($boost = $this->getBoost($entity))) {
            $params['boost'] = $boost;
        }
        $doc = ['id' => $this->generateObjectIdentity($entity)];
        $doc += $this->mapDocument($entity);
        $updateQuery->add($doc, $params);
    }


    /**
     * Adds a delete instruction
     *
     * @param Update $updateQuery
     * @param $entity
     */
    public function addDeleteDocument(Update $updateQuery, $entity)
    {
        $updateQuery->deleteOne($this->generateObjectIdentity($entity));
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
    abstract protected function mapDocument($entity);
}
