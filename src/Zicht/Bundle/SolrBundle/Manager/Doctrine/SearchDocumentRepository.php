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
    public function findIndexableDocuments();

    /**
     * Returns documents by their id
     *
     * @param array $ids
     * @return object[]
     */
    public function findIndexableDocumentsById(array $ids);
}