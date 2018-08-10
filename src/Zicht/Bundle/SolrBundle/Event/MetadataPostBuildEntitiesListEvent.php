<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class MetadataPostBuildEntitiesListEvent
 * @package Zicht\Bundle\SolrBundle\Event
 */
class MetadataPostBuildEntitiesListEvent extends Event
{
    /** @var array */
    private $list;

    /**
     * PostBuildSupportedEntitiesListEvent constructor.
     *
     * @param array $list
     */
    public function __construct(array $list)
    {
        $this->list = $list;
    }

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param  array $list
     */
    public function setList(array $list)
    {
        $this->list = $list;
    }

    /**
     * @param string $className
     * @param array $inheritance
     */
    public function add($className, array $inheritance = [])
    {
        $this->list[]  = ['className' => $className, 'children' => $inheritance];
    }
}
