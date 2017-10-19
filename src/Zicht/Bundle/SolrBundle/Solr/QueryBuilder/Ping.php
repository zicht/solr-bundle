<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Simple request implementing a 'ping'. The ping throws an exception if it is not available.
 */
class Ping implements RequestBuilderInterface
{
    /**
     * @{inheritDoc}
     */
    public function createRequest(Client $httpClient)
    {
        return new Request('GET', sprintf('%sadmin/ping', $httpClient->getConfig('base_url')));
    }
}