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
use Symfony\Component\Console\Output\StreamOutput;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepositoryAdapter;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\WrappedSearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Manager\SolrManager;
use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces\Extractable;

/**
 * Reindex a specified repository or entity in SOLR
 *
 * Calls an Update Query for Entities by default.
 * If an Entity is of the type Extractable the indexer tries first to call the Extract Query.
 * The Extractable interface has an optional resource that's why we fall back to Update in case of missing
 * a resource. The Update Query is much cheaper in that context and can be batched. The Extract is not batchable
 * but for convenience it is enabled in this command.
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
     * @var array
     */
    private $entities;

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
        $this->entities = array();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:reindex')
            ->addArgument('entity', InputArgument::OPTIONAL, 'The entity class to fetch records from. If none supplied we fetch all SOLR-managed entities')
            ->addOption('em', '', InputArgument::OPTIONAL, 'The entity manager to get the repository from', 'default')
            ->addOption('where', 'w', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'An optional where clause to pass to the query builder. The entity\'s query alias is "d" (as in document), so you need to pass criteria such as \'d.dateCreated > CURDATE()\'')
            ->addOption('limit', 'l', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The LIMIT clause to facilitate paging (chunks) of indexing (number of items per chunk)')
            ->addOption('offset', 'o', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The OFFSET clause to facilitate paging (chunks) of indexing (offset to start the chunk at)')
            ->addOption('debug', '', InputOption::VALUE_NONE, 'Debug: i.e. don\'t catch exceptions while indexing')
            ->addOption('delete-first', 'd', InputOption::VALUE_NONE, 'Delete the document from solr before updating')
            ->setDescription('Reindexes entities in the SOLR index');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($entity = $input->getArgument('entity')) {
            $this->entities[] = $entity;

            $output->writeln(sprintf('<comment>One single entity specified</comment>', $entity));
        } else {
            $mappers = $this->solrManager->getMappers();
            foreach ($mappers as $mapper) {
                $this->entities = array_merge($this->entities, $mapper->getClassNames());
            }
            $this->entities = array_unique($this->entities);

            $output->writeln(sprintf('<comment>%d entities found to index (through %s mappers)</comment>', count($this->entities), count($mappers)));
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach($this->entities as $entity) {
            $em = $this->doctrine->getManager($input->getOption('em'));

            $reflection = $em->getClassMetadata($entity)->getReflectionClass();
            $entity = $reflection->name;
            $output->writeln(['', sprintf('<info>%s</info>', $entity)]);

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

            if ($reflection->implementsInterface(Extractable::class)) {
                list($extractableRecords, $updatableRecords) = $this->splitRecords($records);
                list($n, $i) = $this->extractBatch($input, $output, $extractableRecords, $total);
                $output->write("\n");
                $output->writeln(sprintf('Processed (Extracted) %s of %s items.', $i, $n));

                if (count($updatableRecords)) {
                    list($n, $i) = $this->updateBatch($input, $output, $updatableRecords, $total);
                    $output->write("\n");
                    $output->writeln(sprintf('Processed (Updated) %s of %s items.', $i, $n));
                }
            } else {
                list($n, $i) = $this->updateBatch($input, $output, $records, $total);
            }
            $output->write("\n");
            $output->writeln(sprintf('Processed %s of %s items. Peak mem usage: %2d Mb', $i, $n, memory_get_peak_usage() / 1024 / 1024));
            $em->clear();
            gc_collect_cycles();
        }

        return 0;
    }

    /**
     * Updates in batch
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array|Collection $records
     * @return array
     */
    private function updateBatch(InputInterface $input, OutputInterface $output, $records)
    {
        $total = count($records);
        $progress = new ProgressBar($output instanceof StreamOutput ? new StreamOutput($output->getStream()) : $output, $total);
        $progress->display();
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
     * @return array
     */
    private function extractBatch(InputInterface $input, OutputInterface $output, $records)
    {
        $total = count($records);
        $progress = new ProgressBar($output instanceof StreamOutput ? new StreamOutput($output->getStream()) : $output, $total);
        $progress->display();
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

    /**
     * Split the records to being processed by an Update or Extract query
     *
     * @param $records
     * @return array
     */
    private function splitRecords($records)
    {
        $extractableRecords = array_filter($records, [$this, 'hasResource']);
        $updatableRecords = array_filter($records, [$this, 'hasNoResource']);

        return [$extractableRecords, $updatableRecords];
    }

    /**
     * Defines whether a Extractable has a resource.
     *
     * @param Extractable $extractable
     * @return bool
     */
    public function hasResource(Extractable $extractable)
    {
        return is_resource($extractable->getFileResource());
    }

    /**
     * Defines whether a Extractable has not a resource.
     *
     * @param Extractable $extractable
     * @return bool
     */
    public function hasNoResource(Extractable $extractable)
    {
        return !is_resource($extractable->getFileResource());
    }
}
