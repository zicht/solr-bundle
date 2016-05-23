<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Pager;

use Zicht\Bundle\FrameworkExtraBundle\Pager\Pageable;
use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Select;

class SolrPageable implements Pageable
{
    protected $total = null;

    /**
     *
     */
    public function __construct(Client $client, Select $selectQuery)
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
            ->setRows($length)
        ;
    }
}