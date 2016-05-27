<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr;

class DateHelper
{
    /**
     * SOLR date format
     */
    const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * Format date for SOLR
     *
     * @param \DateTime $dateTime
     * @return string
     */
    public static function formatDate($dateTime)
    {
        if (null === $dateTime) {
            return null;
        }
        // force the timezone to be set to UTC, but DON'T mutate the object.
        $cloned = clone $dateTime;
        $cloned->setTimezone(new \DateTimeZone('UTC'));
        return $cloned->format(self::DATE_FORMAT);
    }
}