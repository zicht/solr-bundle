<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
        if ($id = $input->getArgument('id')) {
            $id = array_map('intval', explode(',', $id));
            $records = $repos->find($id);
        } else {
            $records = $repos->findAll();
        }

        $output->writeln(sprintf('Reindexing %d records', count($records)));
        $indexer = $solr->createIndexer();
        $i = 0;
        foreach ($records as $record) {
            $i ++;
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
        }
        $output->writeln('Flushing ' . $i . ' records');
        $indexer->flush();
        $output->writeln("done");
    }
}