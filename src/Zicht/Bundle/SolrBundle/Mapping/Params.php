<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * extra params that will be added to the query and
 * can be used to add for example a boost.
 *
 * @Annotation
 * @Attributes({
 *    @Attribute("value", required=true,  type="array")
 * })
 * @Target("CLASS")
 */
final class Params implements AnnotationInterface
{
    /** @var array */
    public $value;
}