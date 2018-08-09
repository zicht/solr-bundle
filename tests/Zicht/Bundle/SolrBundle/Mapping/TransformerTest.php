<?php
namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use Zicht\Bundle\SolrBundle\Mapping\DateTimeTransformer;

class TransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testDateTimeTransformer()
    {
        $this->assertSame(
            '1988-01-11T23:00:00Z',
            (new DateTimeTransformer())->__invoke(new \DateTime('12 january 1988', new \DateTimeZone('Europe/Amsterdam')))
        );

        $this->assertSame(
            '1988-01-11T23:00:00Z',
            (new DateTimeTransformer())->__invoke(new \DateTimeImmutable('12 january 1988', new \DateTimeZone('Europe/Amsterdam')))
        );
    }
}