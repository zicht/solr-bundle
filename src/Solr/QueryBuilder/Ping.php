<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Simple request implementing a 'ping'. The ping throws an exception if it is not available.
 */
class Ping implements RequestBuilderInterface
{
    public function createRequest(ClientInterface $httpClient)
    {
        return new Request('GET', sprintf('%sadmin/ping', $httpClient->getConfig('base_uri')));
    }
}
