<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

/**
 * Interface to indicate that the object has related objects that need to be deleted.
 *
 * @see \Zicht\Bundle\SolrBundle\Manager\SolrEntityManager
 */
interface DeleteIndexableRelationsInterface
{
    /**
     * Should return an array of objects which should be deleted along with or instead of this object
     *
     * @return object[]
     */
    public function getDeleteIndexableRelations();
}
