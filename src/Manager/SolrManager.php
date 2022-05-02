<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
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
    protected $mappers = [];
    private $repositories;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->mappers = [];
    }

    /**
     * Adds a data mapper
     *
     * @param DataMapperInterface $dataMapper
     * @return void
     */
    public function addMapper($dataMapper)
    {
        $this->mappers[] = $dataMapper;
    }

    /**
     * @return DataMapperInterface[]
     */
    public function getMappers()
    {
        return $this->mappers;
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
        $this->repositories[ $class ] = $repository;
    }

    /**
     * Get a class-specific repository implementation
     *
     * @param string $entityClass
     * @return SearchDocumentRepository|null
     */
    public function getRepository($entityClass)
    {
        if (!isset($this->repositories[ $entityClass ])) {
            return null;
        }

        return $this->repositories[ $entityClass ];
    }

    /**
     * Updates as batch.
     *
     * @param array $records
     * @param callable|null $incrementCallback
     * @param callable|null $errorCallback
     * @param bool $deleteFirst
     * @return array
     */
    public function updateBatch($records, $incrementCallback = null, $errorCallback = null, $deleteFirst = false)
    {
        $update = new QueryBuilder\Update();

        $n = $i = 0;
        foreach ($records as $record) {
            if ($mapper = $this->getMapper($record)) {
                $i++;
                try {
                    if ($deleteFirst) {
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
            $n++;
        }
        if ($incrementCallback) {
            call_user_func($incrementCallback, $n);
        }

        $update->commit();
        $this->client->update($update);

        return [$n, $i];
    }

    /**
     * Extracts as batch.
     *
     * @param array $records
     * @param callable|null $incrementCallback
     * @param callable|null $errorCallback
     * @return array
     */
    public function extractBatch($records, $incrementCallback = null, $errorCallback = null)
    {
        $n = $i = 0;
        foreach ($records as $record) {
            $mapper = $this->getMapper($record);
            if ($mapper === null) {
                continue;
            }

            $i++;
            try {
                $extract = new QueryBuilder\Extract();
                $mapper->extract($extract, $record);
                $this->client->extract($extract);
            } catch (\Exception $e) {
                if ($errorCallback) {
                    call_user_func($errorCallback, $record, $e);
                }
            }

            if ($incrementCallback) {
                call_user_func($incrementCallback, $n);
            }

            $n++;
        }
        call_user_func($incrementCallback, $n);

        return [$n, $i];
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
     * Extract an entity
     *
     * @param object $entity
     * @return bool
     */
    public function extract($entity)
    {
        if (!$this->enabled) {
            return false;
        }

        if ($mapper = $this->getMapper($entity)) {
            $extract = new QueryBuilder\Extract();
            $mapper->extract($extract, $entity);
            $this->client->extract($extract);

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
}
