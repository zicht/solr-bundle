<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\Message\Response;

interface ResponseHandlerInterface
{
    public function handle(Response $response);
}