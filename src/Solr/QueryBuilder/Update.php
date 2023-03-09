<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Zicht\Bundle\SolrBundle\Solr\DateHelper;

class Update extends AbstractQueryBuilder
{
    private const STREAM_MAX_MEMORY = 50 * 1048576; // 50 Mb, default is 2 Mb

    private $stream;

    public function __construct()
    {
        $this->stream = fopen(sprintf('php://temp/maxmemory:%s', self::STREAM_MAX_MEMORY), 'rw');
        fwrite($this->stream, '{');
    }

    /**
     * Add a document to the update request
     *
     * @param array<string, mixed> $document
     * @param array<string, mixed> $params
     * @return $this
     */
    public function add($document, $params = [])
    {
        $this->addInstruction(
            'add',
            [
                'doc' => array_map(
                    static fn ($v) => $v instanceof \DateTime || $v instanceof \DateTimeImmutable ? DateHelper::formatDate($v) : $v,
                    $document
                ),
            ] + $params
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
        $this->addInstruction('commit', new \stdClass());

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
            static fn ($v): array => ['set' => $v],
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
     * @param array|\stdClass|\JsonSerializable $value
     * @return void
     */
    protected function addInstruction($type, $value = [])
    {
        // note that the first comma is overwritten with a '{' before sending the request. This is a performance
        // optimization, skipping the need if an if every call
        fwrite($this->stream, json_encode($type) . ':' . json_encode($value) . ',');
    }

    public function createRequest(ClientInterface $httpClient)
    {
        fseek($this->stream, -1, SEEK_END);
        fwrite($this->stream, '}');
        fseek($this->stream, 0);
        return new Request(
            'POST',
            sprintf('%supdate', $httpClient->getConfig('base_uri')),
            ['Content-Type' => 'application/json'],
            \GuzzleHttp\Psr7\stream_for($this->stream)
        );
    }

    public function getQueryByteSize(): int
    {
        return fstat($this->stream)['size'];
    }
}
