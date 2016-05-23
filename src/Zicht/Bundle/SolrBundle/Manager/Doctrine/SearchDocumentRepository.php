<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

/**
 * Interface SearchDocumentRepository
 * @package Zicht\Bundle\SolrBundle\Manager\Doctrine
 */
interface SearchDocumentRepository
{
    /**
     * Returns all documents that are indexable.
     *
     * Typically simply returns everything
     *
     * @return object[]
     */
    public function findIndexableDocuments($where = null, $limit = null, $offset = null);
}