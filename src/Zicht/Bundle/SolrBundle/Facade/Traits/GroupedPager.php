<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade\Traits;

use Zicht\Bundle\FrameworkExtraBundle\Pager\Pager;
use Zicht\Bundle\SolrBundle\Pager\GroupedSolrPageable;

/**
 * Initializes a pager for a grouped solr query.
 */
trait GroupedPager
{
    /**
     * @{inheritDoc}
     */
    protected function initPager($query)
    {
        $currentPage = $this->searchParams->getOne('page', 0);
        $limit = $this->searchParams->getOne('limit', $this->defaultLimit);
        $pager = new Pager(new GroupedSolrPageable($this->client, $query), $limit);
        $pager->setCurrentPage($currentPage);
        return $pager;
    }
}