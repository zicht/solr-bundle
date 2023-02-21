<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Command\Command;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * Base class for commands interacting with the solr implementation
 */
abstract class AbstractCommand extends Command
{
    public Client $solr;

    public function __construct(Client $solr)
    {
        parent::__construct();

        $this->solr = $solr;
    }
}
