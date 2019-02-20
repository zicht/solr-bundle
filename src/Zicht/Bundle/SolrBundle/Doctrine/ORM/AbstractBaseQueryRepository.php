<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Doctrine\ORM;

use Doctrine\ORM\AbstractQuery;
use Zicht\Bundle\SolrBundle\Mapping\DocumentRepositoryInterface;

/**
 * Class AbstractBaseQueryRepository
 * @package Zicht\Bundle\SolrBundle\Doctrine\ORM
 */
abstract class AbstractBaseQueryRepository  implements DocumentRepositoryInterface, BaseQueryBuilderRepositoryInterface
{

    /**
     * @param null|int $limit
     * @param null|int $offset
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getNewQueryBuilder($limit = null, $offset = null)
    {
        $qb = clone $this->getBaseQueryBuilder();

        if ('' !== $limit && null !== $limit) {
            $qb->setMaxResults((int)$limit);
        }

        if ('' !== $offset && null !== $offset) {
            $qb->setFirstResult((int)$offset);
        }

        return $qb;
    }

    /**
     * @{inheritDoc}
     */
    public function getDocuments($limit = null, $offset = null)
    {
        foreach ($this->getNewQueryBuilder($limit, $offset)->getQuery()->iterate() as $row) {
            yield current($row);
        }
    }

    /**
     * @{inheritDoc}
     */
    public function getDocumentsCount($limit = null, $offset = null)
    {
        return (int)$this
            ->getNewQueryBuilder($limit, $offset)
            ->select('COUNT(d)')
            ->getQuery()
            ->execute(null, AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @{inheritDoc}
     */
    abstract public function getBaseQueryBuilder();

    /**
     * @return string
     */
    abstract protected function getBaseClassName();

    /**
     * free all or given object from memory
     *
     * @param null|object $object
     */
    public function free($object = null)
    {
        if (null === $object) {
            $this->getBaseQueryBuilder()->getEntityManager()->clear($this->getBaseClassName());
        } else {
            $this->getBaseQueryBuilder()->getEntityManager()->detach($object);
        }
    }
}