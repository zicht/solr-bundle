<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Command\Command;
use Zicht\Bundle\SolrBundle\Solr\Client;

class AbstractCommand extends Command
{
    public function __construct(Client $solr)
    {
        parent::__construct();

        $this->solr = $solr;
    }
}