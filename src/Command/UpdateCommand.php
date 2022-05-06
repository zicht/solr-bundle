<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Updates a field within the solr index to a specified value.
 */
class UpdateCommand extends AbstractCommand
{
    /** {@inheritDoc} */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:update')
            ->setDescription('Update a document\'s fields.')
            ->addArgument('select', Console\Input\InputArgument::REQUIRED, "Select these documents to update (e.g.: 'id:abc')")
            ->addArgument('field', Console\Input\InputArgument::REQUIRED, 'Solr field name (e.g "title")')
            ->addArgument('value', Console\Input\InputArgument::REQUIRED, 'Set the value (e.g. "New title")')
        ;
    }

    /** {@inheritDoc} */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $i = 0;
        $update = new Update();
        foreach ($this->solr->getDocumentIds($input->getArgument('select')) as $id) {
            $i ++;
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