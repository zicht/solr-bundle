<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\ORM\EntityRepository;

class SearchDocumentRepositoryAdapter implements SearchDocumentRepository
{
    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }


    public function findIndexableDocuments($offset = null, $limit = null)
    {
        $qb = $this->repository->createQueryBuilder('d');
        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->execute();
    }


    public function findIndexableDocumentsById(array $ids)
    {
        return $this->repository->find($ids);
    }
}