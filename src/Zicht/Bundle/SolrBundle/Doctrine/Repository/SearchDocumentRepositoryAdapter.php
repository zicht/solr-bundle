<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Doctrine\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

/**
 * Doctrine adapter for the SearchDocumentRepository interface
 *
 * @package Zicht\Bundle\SolrBundle\Manager\Doctrine
 */
class SearchDocumentRepositoryAdapter implements SearchDocumentRepositoryInterface
{
    /** @var EntityRepository  */
    private $repository;

    /**
     * Create the SearchDocumentRepository
     *
     * @param EntityRepository $repository
     */
    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @{inheritDoc}
     */
    public function getCountIndexableDocuments($where = null)
    {
        $qb = $this->repository->createQueryBuilder('d');
        $qb->select('COUNT(d)');
        foreach ((array)$where as $w) {
            $qb->andWhere($w);
        }
        return (int)$qb->getQuery()->execute(null, AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }


    /**
     * @{inheritDoc}
     */
    public function findIndexableDocuments($where = null, $limit = null, $offset = null)
    {
        $qb = $this->repository->createQueryBuilder('d');

        foreach ((array)$where as $w) {
            $qb->andWhere($w);
        }

        if ('' !== $limit && null !== $limit) {
            $qb->setMaxResults((int)$limit);
        }

        if ('' !== $offset && null !== $offset) {
            $qb->setFirstResult((int)$offset);
        }

        foreach ($qb->getQuery()->iterate() as $row) {
            yield current($row);
        }
    }
}
