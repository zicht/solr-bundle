<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

/**
 * Interface DocumentRepositoryInterface
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
interface DocumentRepositoryInterface
{
    /**
     * Returns all documents that are indexable. Typically simply
     * returns everything from an entity repository.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return \Generator|object[]
     */
    public function getDocuments($limit = null, $offset = null);


    /**
     * Returns the count of records that are available with the
     * given arguments.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return int
     */
    public function getDocumentsCount($limit = null, $offset = null);
}
