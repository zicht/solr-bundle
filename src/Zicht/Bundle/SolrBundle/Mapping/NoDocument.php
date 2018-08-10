<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * this annotation can be used to mark an entity
 * as excluded when a parent entity is not strict.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class NoDocument implements AnnotationInterface
{
}
