<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use Psr\Http\Message\ResponseInterface;

/**
 * If the request implements this interface, it's response is filtered through the handle() method by `doRequest`
 * in the client. Otherwise the response is returned as-is.
 */
interface ResponseHandlerInterface
{
    /**
     * Handle the response and returns the parsed value
     *
     * @return mixed
     */
    public function handle(ResponseInterface $response);
}
