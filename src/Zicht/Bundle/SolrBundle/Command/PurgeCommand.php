<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Class PurgeCommand
 * @package Zicht\Bundle\SolrBundle\Command
 */
class PurgeCommand extends AbstractCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:purge')
            ->setDescription('Purges SOLR Index')
            ->addArgument('query', InputArgument::OPTIONAL, 'Only delete documents matching this query', '*:*')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $update = new Update();
        $update->delete($input->getArgument('query'));
        $update->commit();

        $result = $this->solr->update($update);

        if ($result) {
            $output->writeln('SOLR Index purged');
        } else {
            $output->writeln('SOLR NOT purged');
        }
    }
}