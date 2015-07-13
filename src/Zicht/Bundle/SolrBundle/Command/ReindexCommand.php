<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity class to fetch records from')
            ->addOption('em', '', InputArgument::OPTIONAL, 'The entity manager to get the repository from', 'default')
            ->addOption('where', 'w', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'An optional where clause to pass to the query builder. The entity\'s query alias is "d" (as in document), so you need to pass criteria such as \'d.dateCreated > CURDATE()\'')
            ->addOption('limit', 'l', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The LIMIT clause to facilitate paging (chunks) of indexing (number of items per chunk)')
            ->addOption('offset', 'o', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The OFFSET clause to facilitate paging (chunks) of indexing (offset to start the chunk at)')
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

        $records = $repos->findIndexableDocuments(
            $input->getOption('where'),
            $input->getOption('limit'),
            $input->getOption('offset')
        );

        $output->writeln(sprintf('Reindexing %d records', count($records)));
        list($n, $i) = $solr->updateBatch($records);
        $output->writeln("Processed $i of $n items");
    }
}