<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use BadMethodCallException;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Attributes({
 *    @Attribute("value", required=true,  type="mixed")
 * })
 * @Target({"ANNOTATION", "PROPERTY"})
 */
final class Marshaller implements AnnotationInterface
{
    /** @var string */
    public $className;

    /** @var string */
    public $method;

    /**
     * @param array $value
     */
    public function __construct(array $value)
    {
        $type = gettype($value['value']);

        switch ($type) {
            case 'array':
                if (2 === count($value['value'])) {
                    list($this->className, $this->method) = $value['value'];
                } else {
                    throw new BadMethodCallException('@Zicht\Bundle\SolrBundle\Mapping\Marshaller should be an class name or class name and method');
                }
                break;
            case 'string':
                $this->className = $value['value'];
                break;
            default:
                throw new BadMethodCallException(sprintf('@Zicht\Bundle\SolrBundle\Mapping\Marshaller should be an array or string, %s given', $type));
        }
    }
}
