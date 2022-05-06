<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\ClientInterface;

/**
 * Interface RequestBuilderInterface
 */
interface RequestBuilderInterface
{
    /**
     * Create an HTTP request that needs to be sent to SOLR.
     *
     * @param ClientInterface $httpClient
     * @return mixed
     */
    public function createRequest(ClientInterface $httpClient);
}