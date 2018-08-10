<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Doctrine\ORM;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Zicht\Bundle\SolrBundle\Mapping\DocumentRepositoryInterface;

/**
 * Class EntityRepositoryWrapper
 * @package Zicht\Bundle\SolrBundle\Doctrine\ORM
 */
class EntityRepositoryWrapper implements DocumentRepositoryInterface, BaseQueryBuilderRepositoryInterface
{
    /** @var \Doctrine\ORM\QueryBuilder  */
    private $baseQuery;

    /**
     * @param EntityRepository $repository
     */
    public function __construct(EntityRepository $repository)
    {
        $this->baseQuery = $repository->createQueryBuilder('d')->where(sprintf('d INSTANCE OF ' . $repository->getClassName()));
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getNewQueryBuilder($limit = null, $offset = null)
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
    public function getBaseQueryBuilder()
    {
        return $this->baseQuery;
    }
}
