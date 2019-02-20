<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Doctrine\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Class EntityRepositoryWrapper
 * @package Zicht\Bundle\SolrBundle\Doctrine\ORM
 */
class EntityRepositoryWrapper extends AbstractBaseQueryRepository
{
    /** @var QueryBuilder  */
    private $baseQuery;
    /** @var string  */
    private $className;

    /**
     * @param EntityRepository $repository
     */
    public function __construct(EntityRepository $repository)
    {
        $this->baseQuery = $repository->createQueryBuilder('d')->where(sprintf('d INSTANCE OF ' . $repository->getClassName()));
        $this->className = $repository->getClassName();
    }

    /**
     * @{inheritDoc}
     */
    public function getBaseQueryBuilder()
    {
        return $this->baseQuery;
    }

    /**
     * @return string
     */
    protected function getBaseClassName()
    {
        return $this->className;
    }
}
