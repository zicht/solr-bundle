<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepositoryAdapter;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Reindex a specified repository or entity in SOLR
 */
class SetCommand extends AbstractCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:set')
            ->addArgument('select', InputArgument::REQUIRED, "Select these documents to update (e.g.: 'id:abc')")
            ->addArgument('field', InputArgument::REQUIRED, 'Solr field name (e.g "title")')
            ->addArgument('value', InputArgument::REQUIRED, 'Set the value (e.g. "New title")')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
    }
}