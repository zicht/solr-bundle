<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Command\Command;
use Zicht\Bundle\SolrBundle\Service\SolrClient;

/**
 * Base class for commands interacting with the solr implementation
 */
class AbstractCommand extends Command
{
    /** @var SolrClient  */
    protected $solr;

    /**
     * Construct the command with the solr service
     *
     * @param SolrClient $solr
     */
    public function __construct(SolrClient $solr)
    {
        parent::__construct();
        $this->solr = $solr;
    }
}