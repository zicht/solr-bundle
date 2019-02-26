<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
declare(strict_types=1);

namespace Zicht\Bundle\SolrBundle\Authorization;

use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;

/**
 * a simpler implementation of the AccessDecisionManager without the
 * support of the TokenInterface and attributes are only update/delete
 * (TRANSACTION_UPDATE or TRANSACTION_DELETE) which can be used to
 * allow or deni the update or delete of entity
 *
 * @package Zicht\Bundle\SolrBundle\Authorization
 */
interface DecisionManagerInterface
{
    const TRANSACTION_UPDATE = 0x01;
    const TRANSACTION_DELETE = 0x02;

    /**
     * @param int $transaction
     * @param DocumentMapperMetadata $meta
     * @param object $object
     *
     * @return bool
     */
    public function decide(int $transaction, DocumentMapperMetadata $meta, $object) :bool;

    /**
     * @param VoterInterface $voter
     * @param int $weight
     */
    public function addVoter(VoterInterface $voter, int $weight = 0) :void;
}