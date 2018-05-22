<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Attributes({
 *    @Attribute("name", required=false,  type="string"),
 * })
 * @Target("PROPERTY")
 */
final class Field
{
    /**
     * the name it is mapped to in the document, if none
     * is given it will use the defined naming strategy
     * to create a mapping name.
     *
     * @var string
     */
    public $name;
}