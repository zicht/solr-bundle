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
interface WrappedSearchDocumentRepository extends SearchDocumentRepository
{
    public function setSourceRepository($repository);
}