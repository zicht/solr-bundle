<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Pager;

use Zicht\Bundle\FrameworkExtraBundle\Pager\Pageable;
use \Solarium\QueryType\Select\Result\Result;

class SolrPageable implements Pageable
{
    protected $total = null;

    /**
     * @param \Solarium\Core\Client\Client $client
     * @param \Solarium\QueryType\Select\Query\Query $selectQuery
     */
    public function __construct($client, $selectQuery)
    {
        $this->client = $client;
        $this->query = $selectQuery;
    }


    /**
     * Returns the absolute total of the pageable set of elements.
     *
     * @return int
     */
    public function getTotal()
    {
        if (!isset($this->total)) {
            $countQuery = clone $this->query;
            // makes sure only the total number of results is fetched.
            $countQuery->setRows(0);
            $this->total = $this->client->select($countQuery)->getNumFound();
        }

        return $this->total;
    }

    /**
     * Sets the range that needs to be displayed on the current page
     *
     * @param int $start
     * @param int $length
     * @return void
     */
    public function setRange($start, $length)
    {
        $this->query
            ->setStart($start)
            ->setRows($length)
        ;
    }
}