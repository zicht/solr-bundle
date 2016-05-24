<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Command\Command;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * Base class for commands interacting with the solr implementation
 */
class AbstractCommand extends Command
{
    /**
     * Construct the command with the solr service
     *
     * @param Client $solr
     */
    public function __construct(Client $solr)
    {
        parent::__construct();

        $this->solr = $solr;
    }
}