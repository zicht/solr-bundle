<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Class PurgeCommand
 */
class DeleteCommand extends AbstractCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:delete')
            ->setAliases(['zicht:solr:purge'])
            ->setDescription('Purges SOLR Index')
            ->addArgument('query', Console\Input\InputArgument::OPTIONAL, 'Only delete documents matching this query', '*:*')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
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