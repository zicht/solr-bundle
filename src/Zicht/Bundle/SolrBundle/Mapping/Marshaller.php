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
 * @Annotation
 * @Attributes({
 *    @Attribute("className", required=true,  type="string"),
 *    @Attribute("method", required=false,  type="string"),
 * })
 * @Target({"ANNOTATION", "PROPERTY"})
 */
final class Marshaller implements AnnotationInterface
{
    /** @var string */
    public $className;

    /** @var string */
    public $method;
}