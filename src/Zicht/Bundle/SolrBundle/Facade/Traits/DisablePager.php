<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

/**
 * Disables pager
 */
trait DisablePager
{
    /**
     * @{inheritDoc}
     */
    protected function initPager($query)
    {
        return null;
    }
}