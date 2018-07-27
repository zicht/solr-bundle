<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\QueryBuilder;

use Psr\Http\Message\RequestInterface;
use Zicht\Http\RequestFactoryInterface;

/**
 * Interface RequestBuilderInterface
 */
interface RequestBuilderInterface
{
    /**
     * Create an HTTP request that needs to be sent to SOLR.
     *
     * @param RequestFactoryInterface $factory
     * @return RequestInterface
     */
    public function createRequest(RequestFactoryInterface $factory);
}