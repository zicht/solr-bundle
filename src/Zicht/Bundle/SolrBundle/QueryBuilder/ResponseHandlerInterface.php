<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\QueryBuilder;

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
     * @param ResponseInterface $response
     * @return mixed
     */
    public function handle(ResponseInterface $response);
}
