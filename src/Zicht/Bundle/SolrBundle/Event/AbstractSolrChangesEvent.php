<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Event;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractSolrChangesEvent extends Event
{
    public function  __construct(ChangeSet $changeSet)
    {
        $this->changeSet = $changeSet;
    }


    public function getChangeSet()
    {
        return $this->changeSet;
    }
}