<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Authorization;

use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;

interface VoterInterface
{
    const ABSTAIN = 0x00;
    const GRANTED = 0x01;
    const DENIED =  0x02;

    /**
     * @param int $transaction
     * @param DocumentMapperMetadata $meta
     * @param object $object
     *
     * @return int either GRANTED, ABSTAIN, or DENIED
     */
    public function vote(int $transaction, DocumentMapperMetadata $meta, $object) :int;
}