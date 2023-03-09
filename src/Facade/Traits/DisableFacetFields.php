<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

trait DisableFacetFields
{
    public function getFacetFields()
    {
        return [];
    }
}
