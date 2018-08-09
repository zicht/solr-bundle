<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Doctrine\ORM;

interface BaseQueryBuilderRepositoryInterface
{
    /**
     * Should return the base query builder that is used
     * for the getDocuments and getDocumentsCount method
     * and the reindex command will use this alter the
     * query based on given user command options.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getBaseQueryBuilder();
}
