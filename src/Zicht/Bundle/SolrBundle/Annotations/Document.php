<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Document extends Annotation
{
    /**
     * if false then a instance of comparison is done
     * instead of a strict comparison (===). So all
     * child classes inherit the same annotations.
     *
     * @var bool
     */
    public $strict = true;

    /** @var string */
    public $repository;
}