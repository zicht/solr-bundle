<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

/**
 * Disables pager
 */
trait DisablePager
{
    /** {@inheritDoc} */
    protected function initPager($query)
    {
        return null;
    }
}