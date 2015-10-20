<?php
/**
 * @author    Gerard van Helden / Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager;

use \Doctrine\Bundle\DoctrineBundle\Registry;
use \Solarium\Core\Client\Client;
use Solarium\Core\Client\Request;

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
    public function updateBatch($records, $incrementCallback = null, $errorCallback = null, $delete = false)
    {
        $update = $this->client->createUpdate();

        $n = $i = 0;
        foreach ($records as $record) {
            if ($mapper = $this->getMapper($record)) {
                $i ++;
                try {
                    if ($delete) {
                        $mapper->delete($this->client, $record, $update);
                    }
                    $mapper->update($this->client, $record, $update);
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
        $update->addCommit();
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


    public function updateFieldValues($documentId, $values)
    {
        $instructions = array();
        foreach ($values as $key => $value) {
            $instructions[$key] = array('set' => $value);
        }
        $data =
            json_encode(
                array(
                    'add' => array(
                        array('id' => $documentId)
                        + $instructions
                    )
                )
            );

        $request = new Request();
        $request->setHandler('update/json');
        $request->setMethod(Request::METHOD_POST);
        $request->setHeaders(
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );

        $request->setRawData($data);
        $this->client->getAdapter()->execute($request, $this->client->getEndpoint());
    }


    public function commit()
    {
        $data = json_encode(array('commit' => array()), JSON_FORCE_OBJECT);

        $request = new Request();
        $request->setHandler('update/json');
        $request->setMethod(Request::METHOD_POST);
        $request->setHeaders(
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );

        $request->setRawData($data);
        $this->client->getAdapter()->execute($request, $this->client->getEndpoint());
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
