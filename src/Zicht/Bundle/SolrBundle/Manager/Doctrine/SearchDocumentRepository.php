<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

/**
 * Interface SearchDocumentRepository
 */
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
     * @return \Generator|object[]
     */
    public function findIndexableDocuments($where = null, $limit = null, $offset = null);


    /**
     * Will get the count of records that will be fetched
     *
     * @param null $where
     * @return int
     */
    public function getCountIndexableDocuments($where = null);
}