<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr;

class DateHelper
{
    /** SOLR date format */
    const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * Format date for SOLR
     *
     * @param \DateTime|\DateTimeImmutable|null $dateTime
     * @return string|null
     */
    public static function formatDate($dateTime)
    {
        if (null === $dateTime) {
            return null;
        }

        // force the timezone to be set to UTC, but DON'T mutate the object.
        if ($dateTime instanceof \DateTimeImmutable) {
            $cloned = $dateTime->setTimezone(new \DateTimeZone('UTC'));
        } else {
            $cloned = clone $dateTime;
            $cloned->setTimezone(new \DateTimeZone('UTC'));
        }

        return $cloned->format(self::DATE_FORMAT);
    }
}
