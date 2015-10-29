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

/**
 * Reindex a specified repository or entity in SOLR
 */
class SetCommand extends ContainerAwareCommand
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
        /** @var $solr \Zicht\Bundle\SolrBundle\Manager\SolrManager */
        $solr = $this->getContainer()->get('zicht_solr.manager');
        $solr->disableTimeout();

        $update = [];
        foreach ($solr->getDocumentIds($input->getArgument('select')) as $id) {
            $update[$id] = [$input->getArgument('field') => $input->getArgument('value')];
        }

        $output->writeln(sprintf('%d documents updated', $solr->updateValues($update)));
    }
}