<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;

/**
 * Trait MethodMapperTrait
 * @package Zicht\Bundle\SolrBundle\Mapping
 */
trait MethodMapperTrait
{
    /** @var string  */
    protected $class;
    /** @var string  */
    protected $method;

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return sprintf('%s::%s()', $this->class, $this->method);
    }
}
