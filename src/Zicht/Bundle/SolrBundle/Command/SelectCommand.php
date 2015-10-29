<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Solarium\Core\Client\Client;
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
class SelectCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:select')
            ->addArgument('query', InputArgument::REQUIRED, "Select these documents (e.g.: 'id:abc')")
            ->addOption('field', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Fields to display")
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Client $solarium */
        $solarium = $this->getContainer()->get('solarium.client');

        $select = $solarium->createSelect();
        if ($fl = $input->getOption('field')) {
            $select->setFields($fl);
        }
        $select->setQuery($input->getArgument('query'));
        foreach ($solarium->execute($select) as $doc) {
            $output->writeln(json_encode($doc->getFields(), JSON_PRETTY_PRINT));
        }
    }
}