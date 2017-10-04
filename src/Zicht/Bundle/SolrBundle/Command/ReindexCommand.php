<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
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
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces\Extractable;

/**
 * Reindex a specified repository or entity in SOLR
 */
class ReindexCommand extends AbstractCommand
{
    /**
     * @var SolrManager
     */
    private $solrManager;

    /**
     * @var Registry
     */
    private $doctrine;

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
            ->setDescription('Reindexes entities in the SOLR index');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $solrManager \Zicht\Bundle\SolrBundle\Manager\SolrManager */

        $output->writeln('Preparing entities ...');

        $em = $this->doctrine->getManager($input->getOption('em'));

        $reflection = $em->getClassMetadata($input->getArgument('entity'))->getReflectionClass();
        $entity = $reflection->name;

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
                ->setSQLLogger(new EchoSQLLogger());
        }
        $output->writeln('Finding indexable documents...');

        $records = $repos->findIndexableDocuments(
            $input->getOption('where'),
            $input->getOption('limit'),
            $input->getOption('offset')
        );

        $total = count($records);

        $output->writeln('Reindexing records ...');
        $progress = new ProgressBar($output, $total);
        $progress->display();

        if ($reflection->implementsInterface(Extractable::class)) {
            list($n, $i) = $this->extractBatch($input, $output, $records, $progress, $total);
        } else {
            list($n, $i) = $this->updateBatch($input, $output, $records, $progress, $total);
        }
        $output->write("\n");
        $output->writeln(sprintf('Processed %s of %s items. Peak mem usage: .%2d Mb', $i, $n, memory_get_peak_usage() / 1024 / 1024));
    }

    /**
     * Updates in batch
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array|Collection $records
     * @param ProgressBar $progress
     * @param int $total
     *
     * @return array
     */
    private function updateBatch(InputInterface $input, OutputInterface $output, $records, $progress, $total)
    {
        list($n, $i) = $this->solrManager->updateBatch(
            $records,
            function ($n) use ($progress, $total, $output) {
                $progress->setProgress($n);
                if ($n == $total) {
                    $progress->finish();
                    $output->write("\n");
                    $output->writeln('Flushing ...');
                }
            },
            function ($record, $e) use ($input, $output) {
                if (!$input->getOption('debug')) {
                    $output->writeln(sprintf('Error indexing record: %s (%s)', (string)$record, $e->getMessage()));
                } else {
                    throw $e;
                }
            },
            (bool)$input->getOption('delete-first')
        );

        return array($n, $i);
    }
    /**
     * Extracts in batches
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array|Collection $records
     * @param ProgressBar $progress
     * @param int $total
     *
     * @return array
     */
    private function extractBatch(InputInterface $input, OutputInterface $output, $records, $progress, $total)
    {
        list($n, $i) = $this->solrManager->extractBatch(
            $records,
            function ($n) use ($progress, $total, $output) {
                $progress->setProgress($n);
                if ($n == $total) {
                    $progress->finish();
                    $output->write("\n");
                    $output->writeln('Flushing ...');
                }
            },
            function ($record, $e) use ($input, $output) {
                if (!$input->getOption('debug')) {
                    $output->write(sprintf('Error indexing record: %s (%s)', (string)$record, $e->getMessage()));
                } else {
                    throw $e;
                }
            }
        );

        return array($n, $i);
    }
}
