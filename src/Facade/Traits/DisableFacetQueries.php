<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

/**
 * Disables facet queries
 */
trait DisableFacetQueries
{
    /** {@inheritDoc} */
    public function getFacetQueries()
    {
        return [];
    }
}