<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Pager;

use Solarium\Core\Client\Client;
use Solarium\QueryType\Select\Query\Query;
use Zicht\Bundle\FrameworkExtraBundle\Pager\Pageable;

/**
 * Class SolrPageable
 *
 * @package Zicht\Bundle\SolrBundle\Pager
 */
class SolrPageable implements Pageable
{
    /**
     * @var null|int
     */
    protected $total = null;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Query
     */
    protected $query;

    /**
     * SolrPageable constructor.
     *
     * @param Client $client
     * @param Query $selectQuery
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