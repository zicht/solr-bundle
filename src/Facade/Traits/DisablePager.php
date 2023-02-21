<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

trait DisablePager
{
    protected function initPager($query)
    {
        return null;
    }
}
