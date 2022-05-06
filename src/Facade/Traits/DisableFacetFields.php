<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

/**
 * Disables facet fields
 */
trait DisableFacetFields
{
    /** {@inheritDoc} */
    public function getFacetFields()
    {
        return [];
    }
}