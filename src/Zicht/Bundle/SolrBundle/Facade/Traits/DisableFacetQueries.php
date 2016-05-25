<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

trait DisableFacetQueries
{
    public function getFacetQueries()
    {
        return [];
    }
}