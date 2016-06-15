<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Event;

final class ChangeSet
{
    public function __construct()
    {
        $this->changes = [];
    }

    public function getChanges()
    {
        return $this->changes;
    }


    public function add($type, $entity)
    {
        $this->changes[] = [$type, $entity];
    }


    public function filter($callback)
    {
        $this->changes = array_filter($this->changes, $callback);
    }


    public function map($callback)
    {
        $this->changes = array_map($callback, $this->changes);
    }
}