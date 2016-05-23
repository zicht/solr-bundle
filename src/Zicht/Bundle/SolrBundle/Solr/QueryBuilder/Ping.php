<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\Client;

class Ping implements RequestBuilderInterface
{
    public function createRequest(Client $httpClient)
    {
        return $httpClient->createRequest('GET', 'admin/ping');
    }
}