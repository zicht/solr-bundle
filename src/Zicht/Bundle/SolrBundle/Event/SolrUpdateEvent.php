<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Zicht\Bundle\SolrBundle\QueryBuilder\Update;

class SolrUpdateEvent extends Event
{
    /** @var Update  */
    private $queryBuilder;
    /** @var object */
    private $entity;

    /**
     * @param Update $update
     * @param object $entity
     */
    public function __construct(Update $update, $entity)
    {
        $this->queryBuilder = $update;
        $this->entity = $entity;
    }

    /**
     * @return Update
     */
    public function getQueryBuilder() :Update
    {
        return $this->queryBuilder;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}