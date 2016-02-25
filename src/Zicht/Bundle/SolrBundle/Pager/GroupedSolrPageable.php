<?php
/**
 * @author Victor Moreno <victor@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Pager;

use Solarium\Client;
use Solarium\QueryType\Select\Query\Query;

class GroupedSolrPageable extends SolrPageable
{
    public function __construct(Client $client, Query $selectQuery, $groupName)
    {
        parent::__construct($client, $selectQuery);
        $this->groupName = $groupName;
    }


    public function getTotal()
    {

        if (!isset($this->total)) {
            $countQuery = clone $this->query;
            // makes sure only the total number of results is fetched.
            $countQuery->setRows(0);
            $this->total = $this->client->select($countQuery)->getGrouping()->getGroups()[$this->groupName]->getNumberOfGroups();
        }
        return $this->total;
    }
}