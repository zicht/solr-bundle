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
 *   @Attribute("value", required=true,  type="string")
 * })
 * @Target("CLASS")
 */
final class IdGenerator implements AnnotationInterface
{

    /**
     * @var string
     */
    public $value;

    /**
     * DocumentIdGenerator constructor.
     *
     * @param array $value
     */
    public function __construct(array $value)
    {
       if (!is_a($value['value'], IdGeneratorInterface::class, true)) {
           throw new \InvalidArgumentException(sprintf('"%s" does not implement "%s" for @IdGenerator<value>', $value['value'], IdGeneratorInterface::class));
       }

       $this->value = $value['value'];
    }
}
