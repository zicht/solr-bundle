<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

/**
 * Disables facet queries
 */
trait DisableFacetQueries
{
    /**
     * {@inheritDoc}
     */
    public function getFacetQueries()
    {
        return [];
    }
}