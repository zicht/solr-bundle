<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
declare(strict_types=1);

namespace Zicht\Bundle\SolrBundle\Authorization;

use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;

/**
 * Class DecisionManager
 *
 * an unanimous decision implementation that only fails when one
 * of the voter returns an VoterInterface::DENIED
 *
 * @package Zicht\Bundle\SolrBundle\Authorization
 */
class UnanimousDecisionManager implements DecisionManagerInterface
{
    /** @var VoterInterface[][] */
    private $voters = [];
    /** @var bool */
    private $isDirty = false;

    /**
     * DecisionManager constructor.
     *
     * @param VoterInterface[] ...$voters
     */
    public function __construct(VoterInterface ...$voters)
    {
        foreach ($voters as $voter) {
            $this->addVoter($voter);
        }
    }

    /**
     * @param VoterInterface $voter
     * @param int $weight
     */
    public function addVoter(VoterInterface $voter, int $weight = 0) :void
    {
        $this->voters[] = [$voter, $weight];
        $this->isDirty = true;
    }

    /** @return \Generator */
    protected function getVoters() :\Generator
    {
        if ($this->isDirty) {
            usort(
                $this->voters,
                function($a, $b) {
                    return $a[1] <=> $b[1];
                }
            );
        }

        foreach ($this->voters as $voter) {
            yield $voter[0];
        }
    }

    /** @inheritdoc */
    public function decide(int $transaction, DocumentMapperMetadata $meta, $object): bool
    {
        foreach ($this->getVoters() as $voter) {
            if (VoterInterface::DENIED === $voter->vote($transaction, $meta, $object)) {
                return false;
            }
        }
        return true;
    }
}
