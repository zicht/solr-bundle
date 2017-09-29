<?php
/**
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces;

/**
 * Interface Extractable
 */
interface Extractable
{
    /**
     * Return the path to the file.
     * Return null when not available.
     *
     * @return string|null
     */
    public function getFile();
}
