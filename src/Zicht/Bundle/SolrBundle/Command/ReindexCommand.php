<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepositoryAdapter;
use \Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;

/**
 * Reindex a specified repository or entity in SOLR
 */
class ReindexCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:reindex')
            ->addOption('em', '', InputArgument::OPTIONAL, 'The entity manager to get the repository from', 'default')
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity class to fetch records from')
            ->addArgument('id', InputArgument::OPTIONAL, 'The id(s) in the repository to reindex')
            ->setDescription('Reindexes entities in the SOLR index')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $solr \Zicht\Bundle\SolrBundle\Manager\SolrManager */
        $solr = $this->getContainer()->get('zicht_solr.manager');
        $entity = $input->getArgument('entity');
        $repos = $this->getContainer()->get('doctrine')->getManager($input->getOption('em'))->getRepository($entity);

        if (!$repos instanceof SearchDocumentRepository) {
            $repos = new SearchDocumentRepositoryAdapter($repos);
        }

        if ($id = $input->getArgument('id')) {
            $records = $repos->findIndexableDocumentsById(array_map('intval', explode(',', $id)));
        } else {
            $records = $repos->findIndexableDocuments();
        }

        $output->writeln(sprintf('Reindexing %d records', count($records)));
        list($n, $i) = $solr->updateBatch($records);
        $output->writeln("Processed $i of $n items");
    }
}