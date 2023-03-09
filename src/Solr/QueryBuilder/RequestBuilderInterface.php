<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\ClientInterface;

interface RequestBuilderInterface
{
    /**
     * Create an HTTP request that needs to be sent to SOLR.
     *
     * @return mixed
     */
    public function createRequest(ClientInterface $httpClient);
}
