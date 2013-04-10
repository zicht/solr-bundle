<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;
use Solarium\Core\Client\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Builder\Solarium\SolariumInterface;

class PingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:solr:ping')
            ->setDescription('Purges SOLR Index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $client Client */
        $client = $this->getContainer()->get('solarium.client');

        $client->ping($client->createPing());
        $output->writeln("Ping succeeded"); // otherwise an exception is thrown
    }
}