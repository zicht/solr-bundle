<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Facade\Traits;

use Zicht\Bundle\FrameworkExtraBundle\Pager\Pager;
use Zicht\Bundle\SolrBundle\Pager\SolrPageable;

/**
 * Initializes a pager based on a regular solr query
 */
trait DefaultPager
{
    /**
     * @{inheritDoc}
     */
    protected function initPager($query)
    {
        $currentPage = $this->searchParams->getOne('page', 0);
        $limit = $this->searchParams->getOne('limit', $this->defaultLimit);
        $pager = new Pager(new SolrPageable($this->client, $query), $limit);
        $pager->setCurrentPage($currentPage);
        return $pager;
    }
}
