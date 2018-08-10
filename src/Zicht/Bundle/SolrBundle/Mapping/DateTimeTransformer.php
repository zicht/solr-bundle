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
 *    @Attribute("weight", required=false, type="int"),
 * })
 * @Target({"PROPERTY", "METHOD"})
 */
class DateTimeTransformer implements TransformInterface, TransformerWeightInterface, AnnotationInterface
{
    /** SOLR date format */
    const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /** @var int */
    public $weight = 0;

    /**
     * @param \DateTimeInterface $data
     * @return mixed
     */
    public function __invoke($data)
    {
        if (!$data instanceof \DateTimeInterface) {
            return $data;
        }

        $date = \DateTime::createFromFormat(\DateTime::ISO8601, $data->format(\DateTime::ISO8601));
        $date->setTimezone(new \DateTimeZone('UTC'));
        return $date->format(self::DATE_FORMAT);
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }
}
