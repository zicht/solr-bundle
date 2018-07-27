<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\QueryBuilder;

use Zicht\Http\RequestFactoryInterface;

/**
 * Simple request implementing a 'ping'. The ping throws an exception if it is not available.
 */
class Ping implements RequestBuilderInterface
{
    /**
     * @{inheritDoc}
     */
    public function createRequest(RequestFactoryInterface $factory)
    {
        return $factory->createRequest('GET', 'admin/ping');
    }
}