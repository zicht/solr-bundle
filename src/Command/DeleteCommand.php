<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

class DeleteCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:solr:delete')
            ->setAliases(['zicht:solr:purge'])
            ->setDescription('Purges SOLR Index')
            ->addArgument('query', InputArgument::OPTIONAL, 'Only delete documents matching this query', '*:*');
    }

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

        return 0;
    }
}
