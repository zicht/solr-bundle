<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\Client;

/**
 * Interface RequestBuilderInterface
 */
interface RequestBuilderInterface
{
    /**
     * Create an HTTP request that needs to be sent to SOLR.
     *
     * @param Client $httpClient
     * @return mixed
     */
    public function createRequest(Client $httpClient);
}