<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;

/**
 * Class Update
 */
class Update extends AbstractQueryBuilder
{
    private $instructions = [];

    /**
     * Add a document to the update request
     *
     * @param array[] $document
     * @param array $params
     * @return self
     */
    public function add($document, $params = [])
    {
        $this->addInstruction(
            'add',
            ['doc' => $document] + $params
        );

        return $this;
    }


    /**
     * Add a commit to the update request
     *
     * @return $this
     */
    public function commit()
    {
        $this->addInstruction('commit', new \stdClass);

        return $this;
    }

    /**
     * Do a delete by id
     *
     * @param string $id
     * @return $this
     */
    public function deleteOne($id)
    {
        $this->addInstruction('delete', ['id' => $id]);

        return $this;
    }

    /**
     * Add a delete to the update request.
     *
     * @param string $query
     * @return self
     */
    public function delete($query)
    {
        $this->addInstruction('delete', ['query' => $query]);

        return $this;
    }

    /**
     * Updates an existing document.
     *
     * @param string $id
     * @param array $values
     * @param array $params
     * @return self
     */
    public function update($id, $values, $params = [])
    {
        $instruction = ['doc' => ['id' => $id]];
        $instruction['doc'] += array_map(
            function($v) {
                return ['set' => $v];
            },
            $values
        );
        $instruction += $params;
        $this->addInstruction('add', $instruction);

        return $this;
    }


    /**
     * Add an instruction to the request.
     *
     * @param string $type
     * @param array $value
     * @return void
     */
    private function addInstruction($type, $value = [])
    {
        $this->instructions[]= [$type, $value];
    }


    /**
     * @{inheritDoc}
     */
    public function createRequest(Client $client)
    {
        $req = $client->createRequest('POST', 'update');

        $req->setHeader('Content-Type', 'application/json');
        $req->setBody(Stream::factory($this->createRequestBody($this->instructions)));

        return $req;
    }
}