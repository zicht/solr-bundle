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
use Zicht\Bundle\SolrBundle\Exception\BadMethodCallException;

/**
 * @Annotation
 * @Attributes({
 *    @Attribute("repository", required=false, type="string"),
 *    @Attribute("children_not_inherit", required=false, type="boolean"),
 *    @Attribute("transformers", required=false, type="array")
 * })
 * @Target("CLASS")
 */
final class Document implements AnnotationInterface
{
    /**
     * a repository class that implements SearchDocumentRepository
     *
     * @var string
     */
    public $repository;

    /**
     * if true then a instance of comparison is done instead of a
     * strict comparison (===). So all child classes inherit the
     * same annotations (except entities with the NoDocument)
     *
     * @var bool
     */
    public $child_inheritance = false;

    /**
     * automatic transformers based on type, the array should
     * be a transformer class as key and type match for value
     * or and array with weight and value.
     *
     * @var array
     */
    public $transformers;

    /**
     * Document constructor.
     *
     * @param array $value
     */
    public function __construct(array $value)
    {
        if (!empty($value['repository'])) {

            if (is_a($value['repository'], DocumentRepositoryInterface::class, true)) {
                throw new BadMethodCallException(sprintf('@Zicht\Bundle\SolrBundle\Mapping\Document::repository should be an instance of "%s" but "%s" was given.', DocumentRepositoryInterface::class, $value['repository']));
            }

            $this->repository = $value['repository'];
        }

        if (isset($value['child_inheritance'])) {
            $this->child_inheritance = $value['child_inheritance'];
        }

        if (!array_key_exists('transformers', $value)) {
            $this->transformers = [
                DateTimeTransformer::class => '/^(?:date(?:time(?:z)?)?|time)(?:_immutable)?$/',
            ];
        } else {
            $this->transformers = (array)$value['transformers'];
        }
    }
}
