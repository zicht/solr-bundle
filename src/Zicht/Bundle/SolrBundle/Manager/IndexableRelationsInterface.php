<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

/**
 * Interface to indicate that the object has related objects that need to be indexed.
 *
 * @see \Zicht\Bundle\SolrBundle\Manager\SolrEntityManager
 */
interface IndexableRelationsInterface
{
    /**
     * Should return an array of objects which should be indexed along with or instead of this object
     *
     * @return object[]
     */
    public function getIndexableRelations();
}
