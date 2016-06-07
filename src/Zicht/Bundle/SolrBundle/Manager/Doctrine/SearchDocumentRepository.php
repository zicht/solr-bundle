<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

interface SearchDocumentRepository
{
    public function findIndexableDocuments($offset = null, $limit = null);
    public function findIndexableDocumentsById(array $ids);
}