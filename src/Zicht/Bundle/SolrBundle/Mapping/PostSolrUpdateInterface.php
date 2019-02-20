<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
declare(strict_types=1);

namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Event\SolrUpdateEvent;

interface PostSolrUpdateInterface
{
    /** @param SolrUpdateEvent $event */
    public function postSolrUpdate(SolrUpdateEvent $event) :void;
}