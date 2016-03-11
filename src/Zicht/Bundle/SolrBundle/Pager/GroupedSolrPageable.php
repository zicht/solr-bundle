<?php
/**
 * @author Victor Moreno <victor@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Pager;

use Solarium\Client;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Grouping\ValueGroup;
use Solarium\QueryType\Select\Result\Result;

/**
 * Class GroupedSolrPageable
 *
 * @package Zicht\Bundle\SolrBundle\Pager
 */
class GroupedSolrPageable extends SolrPageable
{
    /**
     * @var string
     */
    protected $groupName;

    /**
     * GroupedSolrPageable constructor.
     *
     * @param Client $client
     * @param Query $selectQuery
     * @param string $groupName
     */
    public function __construct(Client $client, Query $selectQuery, $groupName)
    {
        parent::__construct($client, $selectQuery);

        $this->groupName = $groupName;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        if (!isset($this->total)) {
            $this->total = 0;

            /** @var Query $countQuery */
            $countQuery = clone $this->query;

            // Remove the row restriction, otherwise the results are limited by this value
            $countQuery->setRows(null);

            /** @var Result $results */
            $results = $this->client->select($countQuery);

            /** @var ValueGroup $group */
            foreach ($results->getGrouping()->getGroups()[$this->groupName] as $group) {
                $this->total += $group->getNumFound();
            }
        }

        return $this->total;
    }
}