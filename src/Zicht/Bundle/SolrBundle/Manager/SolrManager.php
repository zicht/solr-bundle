<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @author Rik van der Kemp <rik@zicht.nl>
 *
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

/**
 * Central manager service for solr features.
 */
class SolrManager
{
    /**
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
    private $repositories;


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
     * Adds a document repository responsible for querying documents when doing a reindex of multiple records
     *
     * @param string $class
     * @param SearchDocumentRepository $repository
     * @return void
     */
    public function addRepository($class, $repository)
    {
        $this->repositories[$class]= $repository;
    }


    /**
     * Get a class-specific repository implementation
     *
     * @param string $entityClass
     * @return SearchDocumentRepository|null
     */
    public function getRepository($entityClass)
    {
        if (!isset($this->repositories[$entityClass])) {
            return null;
        }

        return $this->repositories[$entityClass];
    }


    /**
     * Updates as batch.
     *
     * @param array $records
     * @param callable|null $incrementCallback
     * @param callable|null $errorCallback
     * @param boolean $delete
     * @return array
     */
    public function updateBatch($records, $incrementCallback = null, $errorCallback = null, $delete = false)
    {
        $update = new QueryBuilder\Update();

        $n = $i = 0;
        foreach ($records as $record) {
            if ($mapper = $this->getMapper($record)) {
                $i ++;
                try {
                    if ($delete) {
                        $mapper->delete($update, $record, $update);
                    }
                    $mapper->update($update, $record, $update);
                } catch (\Exception $e) {
                    if ($errorCallback) {
                        call_user_func($errorCallback, $record, $e);
                    }
                }
                if ($incrementCallback) {
                    call_user_func($incrementCallback, $n);
                }
            }
            $n ++;
        }
        call_user_func($incrementCallback, $n);

        $update->commit();
        $this->client->update($update);
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
            $update = new QueryBuilder\Update();
            $mapper->update($update, $entity);
            $update->commit();
            $this->client->update($update);
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
            $update = new QueryBuilder\Update();
            $mapper->delete($update, $entity);
            $update->commit();
            $this->client->update($update);
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

    /**
     * Check if entity is supported
     *
     * @param mixed $entity
     * @return bool
     */
    public function support($entity)
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($entity)) {
                return true;
            }
        }
        return false;
    }
}
