<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepositoryAdapter;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reindex a specified repository or entity in SOLR
 */
class ReindexCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:solr:reindex')
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity class to fetch records from')
            ->addArgument('id', InputArgument::OPTIONAL, 'The id(s) in the repository to reindex')
            ->setDescription('Reindexes entities in the SOLR index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $solr \Zicht\Bundle\SolrBundle\Manager\SolrManager */
        $solr = $this->getContainer()->get('zicht_solr.solr_manager');
        $entity = $input->getArgument('entity');
        $repos = $this->getContainer()->get('doctrine')->getRepository($entity);

        if (!$repos instanceof SearchDocumentRepository) {
            $repos = new SearchDocumentRepositoryAdapter($repos);
        }

        if ($id = $input->getArgument('id')) {
            $records = $repos->findIndexableDocumentsById(array_map('intval', explode(',', $id)));
        } else {
            $records = $repos->findIndexableDocuments();
        }

        $output->writeln(sprintf('Reindexing %d records', count($records)));
        $indexer = $solr->createIndexer();
        $i = $j = 0;

        foreach ($records as $record) {
            $i ++;
            $j ++;
            $builder = $solr->getBuilderForEntity($record);
            if ($builder) {
                $indexer->addDocumentBuilder($builder);
                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $output->writeln(sprintf(' - Added record %d of %d', $i, count($records)));
                }
            } else {
                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $output->writeln(sprintf(' - Skipped record %d of %d', $i, count($records)));
                }
            }
            if ($j > 1000) {
                $output->writeln('Flushing ' . $j . ' records');
                $indexer->flush();
                $j = 0;
            }
        }
        $output->writeln('Flushing ' . $j . ' records');
        $indexer->flush();
        $output->writeln("Processed $i items");
    }
}