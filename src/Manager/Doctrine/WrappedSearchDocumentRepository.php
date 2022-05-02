<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

/**
 * An implementation of the SearchDocumentRepository which wrap another one. This is useful if you want to invert
 * control by having a service that depends on other things, but needs the repository to get it's data from.
 *
 * Typically useful when you need optimizations when doing a bulk reindex.
 */
interface WrappedSearchDocumentRepository extends SearchDocumentRepository
{
    /**
     * Sets the repository where the documents originate from
     *
     * @param object $repository
     * @return mixed
     */
    public function setSourceRepository($repository);
}