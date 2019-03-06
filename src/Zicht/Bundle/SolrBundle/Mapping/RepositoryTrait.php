<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Doctrine\Common\Persistence\ObjectManager;
use Zicht\Bundle\SolrBundle\Doctrine\ORM\EntityRepositoryWrapper;
use Zicht\Bundle\SolrBundle\Service\SolrManager;

trait RepositoryTrait
{
    /**
     * @param DocumentMapperMetadata $meta
     * @param ObjectManager $objectManager
     * @param SolrManager $solrManager
     *
     * @return DocumentRepositoryInterface
     */
    private function getRepository(DocumentMapperMetadata $meta, ObjectManager $objectManager, SolrManager $solrManager)
    {
        if (null === $repo = $solrManager->getRepository($meta->getClassName())) {
            $repo = $objectManager->getRepository($meta->getClassName());

            if (!$repo instanceof DocumentRepositoryInterface) {
                $repo = new EntityRepositoryWrapper($repo);
            }
        }

        return $repo;
    }
}