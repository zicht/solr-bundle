<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use \Doctrine\ORM\EntityRepository;

/**
 * Doctrine adapter for the SearchDocumentRepository interface
 *
 * @package Zicht\Bundle\SolrBundle\Manager\Doctrine
 */
class SearchDocumentRepositoryAdapter implements SearchDocumentRepository
{
    /**
     * @param EntityRepository $repository
     */
    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return object[]
     */
    public function findIndexableDocuments()
    {
        return $this->repository->findAll();
    }


    /**
     * @param array $ids
     * @return object
     */
    public function findIndexableDocumentsById(array $ids)
    {
        return $this->repository->find($ids);
    }
}