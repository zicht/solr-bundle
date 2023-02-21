<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Extract as ExtractQuery;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update as UpdateQuery;

/**
 * Central manager service for solr features.
 */
class SolrManager
{
    public ?UpdateQuery $update = null;

    public ?ExtractQuery $extract = null;

    /** @var Client */
    protected $client;

    /** @var bool */
    protected $enabled = true;

    /** @var DataMapperInterface[] */
    protected $mappers = [];

    private $repositories;

    public function __construct(Client $client)
    {
        $this->client = $client;
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
        $this->repositories[$class] = $repository;
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
     * @param array $records
     * @param callable|null $incrementCallback
     * @param callable|null $errorCallback
     * @param bool $deleteFirst
     * @return array
     */
    public function updateBatch($records, $incrementCallback = null, $errorCallback = null, $deleteFirst = false)
    {
        $this->update = new UpdateQuery();

        $n = $i = 0;
        foreach ($records as $record) {
            if ($mapper = $this->getMapper($record)) {
                ++$i;
                try {
                    if ($deleteFirst) {
                        $mapper->delete($this->update, $record);
                    }
                    $mapper->update($this->update, $record);
                } catch (\Exception $e) {
                    if ($errorCallback) {
                        call_user_func($errorCallback, $record, $e);
                    }
                }
                if ($incrementCallback) {
                    call_user_func($incrementCallback, $n);
                }
            }
            ++$n;
        }
        if ($incrementCallback) {
            call_user_func($incrementCallback, $n);
        }

        $this->update->commit();
        $this->client->update($this->update);
        $this->update = null;

        return [$n, $i];
    }

    /**
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

            ++$i;
            try {
                $this->extract = new ExtractQuery();
                $mapper->extract($this->extract, $record);
                $this->client->extract($this->extract);
                $this->extract = null;
            } catch (\Exception $e) {
                if ($errorCallback) {
                    call_user_func($errorCallback, $record, $e);
                }
            }

            if ($incrementCallback) {
                call_user_func($incrementCallback, $n);
            }

            ++$n;
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
            $this->update = new UpdateQuery();
            $mapper->update($this->update, $entity);
            $this->update->commit();
            $this->client->update($this->update);
            $this->update = null;

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
            $this->extract = new ExtractQuery();
            $mapper->extract($this->extract, $entity);
            $this->client->extract($this->extract);
            $this->extract = null;

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
            $this->update = new UpdateQuery();
            $mapper->delete($this->update, $entity);
            $this->update->commit();
            $this->client->update($this->update);
            $this->update = null;

            return true;
        }

        return false;
    }

    /**
     * Enables or disabled the solr manager.
     *
     * @param bool $enabled
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
     * @return DataMapperInterface|null
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
