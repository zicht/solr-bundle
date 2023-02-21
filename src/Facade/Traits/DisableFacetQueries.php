<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

trait DisableFacetQueries
{
    public function getFacetQueries()
    {
        return [];
    }
}
