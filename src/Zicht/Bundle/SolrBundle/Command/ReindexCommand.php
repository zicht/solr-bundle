<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepositoryAdapter;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\WrappedSearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Manager\SolrManager;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * Reindex a specified repository or entity in SOLR
 */
class ReindexCommand extends AbstractCommand
{
    /**
     * Setup the reindex command
     *
     * @param Client $solr
     * @param SolrManager $solrManager
     * @param Registry $doctrine
     */
    public function __construct(Client $solr, SolrManager $solrManager, Registry $doctrine)
    {
        parent::__construct($solr);

        $this->solrManager = $solrManager;
        $this->doctrine = $doctrine;
    }

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
            ->addOption('debug', '', InputOption::VALUE_NONE, 'Debug: i.e. don\'t catch exceptions while indexing')
            ->addOption('delete-first', 'd', InputOption::VALUE_NONE, 'Delete the document from solr before updating')
            ->setDescription('Reindexes entities in the SOLR index')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $solrManager \Zicht\Bundle\SolrBundle\Manager\SolrManager */

        $em = $this->doctrine->getManager($input->getOption('em'));

        $entity = $em->getClassMetadata($input->getArgument('entity'))->getReflectionClass()->name;

        if (null !== ($repos = $this->solrManager->getRepository($entity))) {
            if ($repos instanceof WrappedSearchDocumentRepository) {
                $repos->setSourceRepository($em->getRepository($entity));
            }
        } else {
            $repos = $em->getRepository($entity);

            if (!$repos instanceof SearchDocumentRepository) {
                $repos = new SearchDocumentRepositoryAdapter($repos);
            }
        }

        if ($input->getOption('debug')) {
            $this->doctrine
                ->getConnection()
                ->getConfiguration()
                ->setSQLLogger(new EchoSQLLogger())
            ;
        }

        $output->writeln("Querying records ...");
        $records = $repos->findIndexableDocuments(
            $input->getOption('where'),
            $input->getOption('limit'),
            $input->getOption('offset')
        );

        $total = count($records);

        $output->writeln("Reindexing records ...");
        $progress = new ProgressBar($output, $total);
        $progress->display();
        list($n, $i) = $this->solrManager->updateBatch(
            $records,
            function($n) use($progress, $total) {
                $progress->setProgress($n);
            },
            function($record, $e) use($input, $output) {
                if (!$input->getOption('debug')) {
                    $output->write(sprintf("\nError indexing record: %s (%s)\n", (string)$record, $e->getMessage()));
                } else {
                    throw $e;
                }
            },
            (bool)$input->getOption('delete-first')
        );
        $progress->setProgress($total);
        $output->write("\n");
        $output->writeln("Processed $i of $n items. Peak mem usage: " . sprintf('.%2d Mb', memory_get_peak_usage() / 1024 / 1024));
    }
}