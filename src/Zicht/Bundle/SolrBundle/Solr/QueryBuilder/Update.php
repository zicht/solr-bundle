<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;


use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;

class Update extends AbstractQueryBuilder
{
    public function __construct()
    {
        $this->instructions = [];
    }


    public function add($document, $params = [])
    {
        $value = ['doc' => $document];
        foreach ($params as $key => $value) {
            if ($key === 'doc') {
                throw new \InvalidArgumentException("You are not supposed to pass the actual document as an extra parameter");
            }
            $value[$key] = $value;
        }
        $this->addInstruction('add', $value);
        return $this;
    }


    public function commit()
    {
        $this->addInstruction('commit', new \stdClass);
        return $this;
    }


    public function deleteOne($id)
    {
        $this->addInstruction('delete', ['id' => $id]);
        return $this;
    }

    public function delete($query)
    {
        $this->addInstruction('delete', ['query' => $query]);
        return $this;
    }


    private function addInstruction($type, $value = [])
    {
        $this->instructions[]= [$type, $value];
    }

    public function createRequest(Client $client)
    {
        $req = $client->createRequest('POST', 'update');
        $req->setHeader('Content-Type', 'application/json');

        $body = '{';
        $isFirst = true;
        $i = 0;
        // The reason this is not encoded using json_encode, is that by the spec of the
        // SOLR handler, a key may occur multiple times in the same object.
        foreach ($this->instructions as list($key, $value)) {
            if (!$isFirst) {
                $body .= ",";
            } else {
                $isFirst = false;
            }
            $valueJson = json_encode($value);

            if (!$valueJson) {
                throw new \UnexpectedValueException("Could not convert to JSON because of an error: " . json_last_error_msg());
            }

            $body .= json_encode($key) . ':' . json_encode($value);
        }
        $body .= '}';

        $req->setBody(Stream::factory($body));

        return $req;
    }
}