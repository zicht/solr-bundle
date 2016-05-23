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
     * Adds a document repository
     *
     * @param SearchDocumentRepository $dataMapper
     * @return void
     */
    public function addRepository($class, $repository)
    {
        $this->repositories[$class]= $repository;
    }


    /**
     * Get a class-specific repository implementation
     *
     * @param $entity
     * @return SearchDocumentRepository|null
     */
    public function getRepository($entity)
    {
        if (!isset($this->repositories[$entity])) {
            return null;
        }

        return $this->repositories[$entity];
    }


    /**
     * Updates as batch. Acts as a stub for future optimization.
     *
     * @param array $records
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


    public function updateFieldValues($documentId, $values)
    {
        die('TODO');

//        $instructions = array();
//        foreach ($values as $key => $value) {
//            $instructions[$key] = array('set' => $value);
//        }
//        $data =
//            json_encode(
//                array(
//                    'add' => array(
//                        array('id' => $documentId)
//                        + $instructions
//                    )
//                )
//            );
//
//        $request = new Request();
//        $request->setHandler('update/json');
//        $request->setMethod(Request::METHOD_POST);
//        $request->setHeaders(
//            array(
//                'Content-Type: application/json',
//                'Content-Length: ' . strlen($data)
//            )
//        );
//
//        $request->setRawData($data);
//        $this->client->getAdapter()->execute($request, $this->client->getEndpoint());
    }


    public function commit()
    {
//        $data = json_encode(array('commit' => array()), JSON_FORCE_OBJECT);
//
//        $request = new Request();
//        $request->setHandler('update/json');
//        $request->setMethod(Request::METHOD_POST);
//        $request->setHeaders(
//            array(
//                'Content-Type: application/json',
//                'Content-Length: ' . strlen($data)
//            )
//        );
//
//        $request->setRawData($data);
//        $this->client->getAdapter()->execute($request, $this->client->getEndpoint());
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
     * Disables the timeout on all client's endpoints. Convenience for command line usage.
     *
     * @return void
     */
    public function disableTimeout()
    {
        die('TODO');
        $this->setTimeout(0);
    }


    /**
     * Set the timeout for all the client's endpoints.
     *
     * @param string $timeout
     * @return void
     */
    public function setTimeout($timeout)
    {
        die('TODO');
        foreach ($this->client->getEndpoints() as $endpoint) {
            $endpoint->setTimeout($timeout);
        }
    }

    /**
     * Get all document ids for the specified query.
     */
    public function getDocumentIds($query, $fieldName = 'id')
    {
        die('TODO');
//        $ret = [];
//        $select = $this->client->createSelect();
//        $select->setFields($fieldName);
//        $select->setQuery($query);
//        foreach ($this->client->execute($select) as $doc) {
//            $ret[]= $doc->$fieldName;
//        }
//        return $ret;
    }


    /**
     * Update the values. All keys must have solr document ids, and all values should be key => value mappings for
     * each of the values to be set.
     *
     * @param array $values
     * @return int
     */
    public function updateValues($documents)
    {
        die('TODO');
//
//        $found = 0;
//
//        $update = $this->client->createUpdate();
//        foreach ($documents as $id => $values) {
//            /** @var Document $doc */
//            $doc = $update->createDocument();
//            foreach ($values as $fieldName => $value) {
//                $doc->setFieldModifier($fieldName, Document::MODIFIER_SET);
//                // NOTE it seems that 'null' values aren't working here.
//                $doc->setField($fieldName, $value);
//            }
//            $doc->setKey('id', $id);
//            $update->addDocument($doc);
//            $found ++;
//        }
//
//        // if the docs are not in solr, don't bother.
//        if ($found > 0) {
//            $update->addCommit();
//            $this->client->execute($update);
//        }
//
//        return $found;
    }
}
