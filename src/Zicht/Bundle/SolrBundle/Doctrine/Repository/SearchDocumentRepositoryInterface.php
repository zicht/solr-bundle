<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Doctrine\Repository;

/**
 * Interface SearchDocumentRepositoryInterface
 */
interface SearchDocumentRepositoryInterface
{
    /**
     * Returns all documents that are indexable. Typically simply
     * returns everything from the repository.
     *
     * @param string[] $where
     * @param int $limit
     * @param int $offset
     * @return \Generator|object[]
     */
    public function findIndexableDocuments($where = null, $limit = null, $offset = null);


    /**
     * Returns the count of records that will be fetched with the
     * findIndexableDocuments method.
     *
     * @param null $where
     * @return int
     */
    public function getCountIndexableDocuments($where = null);
}