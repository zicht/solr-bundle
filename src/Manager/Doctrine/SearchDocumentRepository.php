<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

interface SearchDocumentRepository
{
    /**
     * Returns all documents that are indexable.
     *
     * Typically simply returns everything from the repository
     *
     * @param string[] $where
     * @param int $limit
     * @param int $offset
     * @return object[]
     */
    public function findIndexableDocuments($where = null, $limit = null, $offset = null);
}
