<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

class Client
{
    public function __construct(GuzzleClient $client)
    {
        $this->http = $client;
    }


    public function select($query)
    {
        if (is_array($query)) {
            $query = new QueryBuilder\Select($query);
        }

        return $this->doRequest($query);
    }


    public function update($update)
    {
        return $this->doRequest($update);
    }


    protected function doRequest(QueryBuilder\RequestBuilderInterface $handler)
    {
        $request = $handler->createRequest($this->http);
        try {
            $response = $this->http->send($request);
            if ($handler instanceof QueryBuilder\ResponseHandlerInterface)  {
                $response = $handler->handle($response);
            }
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            var_dump($response->getBody()->getContents());
            throw new Exception($e->getMessage(), null, $e);
        }

        return $response;
    }

    public function ping()
    {
        return $this->doRequest(new QueryBuilder\Ping());
    }
}