<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Mapping;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class DateTransformer implements TransformInterface
{

    /** SOLR date format */
    const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @param mixed $data
     * @return mixed
     */
    public function __invoke($data)
    {
        // both DateTime and DateTimeImmutable support the setTimezone method
        // (even the DateTimeImmutable) and the \DateTimeInterface not.
        if (!$data instanceof \DateTime && !$data instanceof \DateTimeImmutable) {
            return $data;
        }

        /** @var \DateTime|\DateTimeImmutable $cloned */
        $cloned = clone $data;
        $cloned->setTimezone(new \DateTimeZone('UTC'));
        return $cloned->format(self::DATE_FORMAT);
    }
}