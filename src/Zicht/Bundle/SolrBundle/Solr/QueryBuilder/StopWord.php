<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;

class StopWord implements RequestBuilderInterface, ResponseHandlerInterface
{
    /**
     * @var string
     */
    private $managed;

    /**
     * @var string
     */
    private $method;

    public function __construct($managed, $method = 'GET')
    {
        $this->managed = $managed;
        $this->method = $method;
    }

    /**
     * @{inheritDoc}
     */
    public function createRequest(Client $client)
    {
        return $client->createRequest('GET', );
    }

    /**
     * @{inheritDoc}
     */
    public function handle(Response $response)
    {
        $contentType = $response->getHeader('Content-Type');
        if (preg_match('!^application/json!', $contentType) || preg_match('!^text/plain!', $contentType)) {
            $response = json_decode($response->getBody()->getContents());
        }
        return $response;
    }
}