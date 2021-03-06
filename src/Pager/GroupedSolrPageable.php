<?php
/**
 * @author Victor Moreno <victor@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Pager;

/**
 * Paging for grouped solr queries.
 */
class GroupedSolrPageable extends SolrPageable
{
    /**
     * {@inheritDoc}
     */
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