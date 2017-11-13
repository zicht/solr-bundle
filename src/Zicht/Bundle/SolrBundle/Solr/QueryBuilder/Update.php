<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Zicht\Bundle\SolrBundle\Solr\DateHelper;

/**
 * Class Update
 */
class Update extends AbstractQueryBuilder
{
    private $stream = null;

    /**
     * Initialize the update request.
     */
    public function __construct()
    {
        $this->stream = fopen('php://temp', 'rw');
        fwrite($this->stream, '{');
    }

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
            ['doc' => array_map(
                function ($v) {
                    if ($v instanceof \DateTime) {
                        $v = DateHelper::formatDate($v);
                    }

                    return $v;
                },
                $document
            )] + $params
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
            function ($v) {
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
    protected function addInstruction($type, $value = [])
    {
        // note that the first comma is overwritten with a '{' before sending the request. This is a performance
        // optimization, skipping the need if an if every call
        fwrite($this->stream, json_encode($type) . ':' . json_encode($value) . ',');
    }


    /**
     * @{inheritDoc}
     */
    public function createRequest(ClientInterface $httpClient)
    {
        fseek($this->stream, -1, SEEK_END);
        fwrite($this->stream, '}');
        fseek($this->stream, 0);
        return new Request(
            'POST',
            sprintf('%supdate', $httpClient->getConfig('base_url')),
            ['Content-Type' => 'application/json'],
            \GuzzleHttp\Psr7\stream_for($this->stream)
        );
    }
}