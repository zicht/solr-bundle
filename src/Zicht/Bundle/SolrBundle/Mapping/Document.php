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
use Zicht\Bundle\SolrBundle\Doctrine\Repository\SearchDocumentRepositoryInterface;

/**
 * @Annotation
 * @Attributes({
 *    @Attribute("strict", required=false,  type="bool"),
 *    @Attribute("exclude", required=false, type="array"),
 *    @Attribute("repository", required=false, type="string")
 * })
 * @Target("CLASS")
 */
final class Document implements AnnotationInterface
{
    public function __construct(array $value)
    {
        if (!empty($value['repository'])) {
            if (is_a($value['repository'], SearchDocumentRepositoryInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf('@Document expected a "%s" instance for a repository but "%s" was given.', SearchDocumentRepositoryInterface::class, $value['repository']));
            }

            $this->repository = $value['repository'];
        }

        if (isset($value['strict'])) {
            $this->strict = $value['strict'];
        }

        if (isset($value['exclude'])) {
            $this->exclude = $value['exclude'];
        }
    }


    /**
     * if false then a instance of comparison is done
     * instead of a strict comparison (===). So all
     * child classes inherit the same annotations.
     *
     * @var bool
     */
    public $strict = true;

    /**
     * exclusion list of classes for when not running in
     * strict modes that will not inhered this mapping
     *
     * @var array
     */
    public $exclude = [];

    /**
     * a repository class that implements SearchDocumentRepository
     *
     * @var string
     */
    public $repository;
}