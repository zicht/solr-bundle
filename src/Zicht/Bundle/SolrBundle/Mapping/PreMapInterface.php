<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
declare(strict_types=1);

namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Event\MapEvent;

interface PreMapInterface
{
    /** @param MapEvent $event */
    public function preMap(MapEvent $event) :void;
}