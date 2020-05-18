<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\ORM\EntityRepository;

/**
 * Doctrine adapter for the SearchDocumentRepository interface
 *
 * @package Zicht\Bundle\SolrBundle\Manager\Doctrine
 */
class SearchDocumentRepositoryAdapter implements SearchDocumentRepository
{
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
     * {@inheritDoc}
     */
    public function findIndexableDocuments($where = null, $limit = null, $offset = null)
    {
        $qb = $this->repository->createQueryBuilder('d');

        if ($where) {
            foreach ((array)$where as $w) {
                $qb->andWhere($w);
            }
        }
        if ('' !== $limit && null !== $limit) {
            $qb->setMaxResults((int)$limit);
        }
        if ('' !== $offset && null !== $offset) {
            $qb->setFirstResult((int)$offset);
        }
        return $qb->getQuery()->execute();
    }
}