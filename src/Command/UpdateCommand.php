<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Updates a field within the solr index to a specified value.
 */
class UpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:solr:update')
            ->setDescription('Update a document\'s fields.')
            ->addArgument('select', InputArgument::REQUIRED, "Select these documents to update (e.g.: 'id:abc')")
            ->addArgument('field', InputArgument::REQUIRED, 'Solr field name (e.g "title")')
            ->addArgument('value', InputArgument::REQUIRED, 'Set the value (e.g. "New title")');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $i = 0;
        $update = new Update();
        foreach ($this->solr->getDocumentIds($input->getArgument('select')) as $id) {
            ++$i;
            $update->update($id, [$input->getArgument('field') => $input->getArgument('value')]);
        }
        if ($i > 0) {
            $update->commit();
            $this->solr->update($update);
        }

        $output->writeln(sprintf('%d document(s) updated', $i));

        return 0;
    }
}
