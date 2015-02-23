<?php
/**
 * @author    Gerard van Helden / Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager;

use \Doctrine\Bundle\DoctrineBundle\Registry;
use \Solarium\Core\Client\Client;

/**
 * Central manager service for solr features.
 */
class SolrManager
{
    /**
     * Solarium client
     *
     * @var Client
     */
    protected $client = null;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var DataMapperInterface[]
     */
    protected $mappers = array();


    /**
     * Constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->mappers = array();
    }


    /**
     * Adds a data mapper
     *
     * @param DataMapperInterface $dataMapper
     * @return void
     */
    public function addMapper($dataMapper)
    {
        $this->mappers[]= $dataMapper;
    }


    /**
     * Updates as batch. Acts as a stub for future optimization.
     *
     * @param array $records
     * @return array
     */
    public function updateBatch($records)
    {
        $i = $n = 0;
        foreach ($records as $record) {
            $i ++;
            if ($this->update($record)) {
                $n ++;
            }
        }
        return array($n, $i);
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

        if ($mapper = $this->getMapper($entity)) {
            $mapper->update($this->client, $entity);
            return true;
        }
        return false;
    }


    /**
     * Delete an entity
     *
     * @param mixed $entity
     * @return bool
     */
    public function delete($entity)
    {
        if (!$this->enabled) {
            return false;
        }

        if ($mapper = $this->getMapper($entity)) {
            $mapper->delete($this->client, $entity);
            return true;
        }
        return false;
    }

    /**
     * Enables or disabled the solr manager.
     *
     * @param boolean $enabled
     * @return void
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }


    /**
     * Returns a mapper based on the entity's type.
     *
     * @param mixed $entity
     * @return DataMapperInterface
     */
    protected function getMapper($entity)
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($entity)) {
                return $mapper;
            }
        }

        return null;
    }
}
