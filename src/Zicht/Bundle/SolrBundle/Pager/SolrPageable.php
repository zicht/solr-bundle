<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Pager;

use Zicht\Bundle\FrameworkExtraBundle\Pager\Pageable;

class SolrPageable implements Pageable
{
    private $results = null;

    function __construct(\Solarium\QueryType\Select\Result\Result $results)
    {
        $this->results = $results;
    }


    /**
     * Returns the absolute total of the pageable set of elements.
     *
     * @return int
     */
    function getTotal()
    {
        return $this->getResults()->getNumFound();
    }

    /**
     * Sets the range that needs to be displayed on the current page
     *
     * @param int $start
     * @param int $length
     * @return void
     */
    function setRange($start, $length)
    {
        $this->limit($length)->offset($start);
    }


    /**
     * Adds a limit, or removes it if the given limit is not greater than 0
     *
     * @param int $limit
     * @return self
     */
    function limit($limit)
    {
        $this->_limit = null;

        if (!is_string($limit)) {
            if ($limit > 0) {
                $this->_limit = $limit;
            }
        } else {
            if (!empty($limit)) {
                $this->_limit = $limit;
            }
        }

        return $this;
    }

    /**
     * Adds an offset, or removes it if the given limit is not greater than or equal to 0
     *
     * @param int $offset
     * @return self
     */
    function offset($offset)
    {
        if ($offset >= 0) {
            $this->_offset = $offset;
        } else {
            $this->_offset = null;
        }

        return $this;
    }

    public function getResults()
    {
        return $this->results;
    }
}