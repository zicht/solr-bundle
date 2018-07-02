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
 * This hold the information of extra fields that are not
 * mapped directly to the entity and are added to the solr
 * document.
 *
 * @Annotation
 * @Attributes({
 *    @Attribute("value", required=true,  type="array")
 * })
 * @Target({"CLASS", "METHOD"})
 */
final class Fields implements AnnotationInterface
{
    /** @var array  */
    public $value;
}