<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PingCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:solr:ping')
            ->setDescription('Do a PING request on the client');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->solr->ping();

        return 0;
    }
}
