<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Cache;

class CacheItem
{
    /** @var string */
    public $key;
    /** @var mixed */
    public $data;
    /** @var \DateTimeInterface */
    public $ttl;
}