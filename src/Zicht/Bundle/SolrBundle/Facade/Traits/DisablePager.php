<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Select;

trait DisablePager
{
    protected function initPager($query)
    {
        return null;
    }
}