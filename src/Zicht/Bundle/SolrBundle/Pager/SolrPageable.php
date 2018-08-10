<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Pager;

use Zicht\Bundle\FrameworkExtraBundle\Pager\Pageable;
use Zicht\Bundle\SolrBundle\Service\SolrClient;
use Zicht\Bundle\SolrBundle\QueryBuilder\Select;

/**
 * Implements 'pageable' for a solr query. Assumes that the `numFound` will be in the response, and the query's
 * `start' and `rows' may be overwritten.
 */
class SolrPageable implements Pageable
{
    /** @var null|int  */
    protected $total = null;
    /** @var SolrClient  */
    protected $client;
    /** @var Select  */
    protected $query;

    /**
     * Construct the pager.
     *
     * @param SolrClient $client
     * @param Select $selectQuery
     */
    public function __construct(SolrClient $client, Select $selectQuery)
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
            $response = $this->client->select($countQuery);

            $this->total = $response->response->numFound;
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
            ->setRows($length);
    }
}
