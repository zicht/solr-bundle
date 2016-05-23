<?php
/**
 * @author Victor Moreno <victor@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Pager;

use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Select;

class GroupedSolrPageable extends SolrPageable
{
    public function getTotal()
    {
        if (!isset($this->total)) {
            $countQuery = clone $this->query;
            // makes sure only the total number of results is fetched.
            $countQuery->setRows(0);
            $response = $this->client->select($countQuery);

            foreach ($response->grouped as $key => $group) {
                $this->total = $group->ngroups;
            }
        }
        return $this->total;
    }
}