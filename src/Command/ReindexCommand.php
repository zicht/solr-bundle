<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepositoryAdapter;
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
    private SolrManager $solrManager;

    private ManagerRegistry $doctrine;

    /** @var class-string[] */
    private array $entities = [];

    public function __construct(Client $solr, SolrManager $solrManager, ManagerRegistry $doctrine)
    {
        parent::__construct($solr);

        $this->solrManager = $solrManager;
        $this->doctrine = $doctrine;
    }

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
        /** @var class-string $entity */
        if ($entity = $input->getArgument('entity')) {
            $this->entities[] = $entity;

            $output->writeln('<comment>One single entity specified</comment>');
        } else {
            $mappers = $this->solrManager->getMappers();
            foreach ($mappers as $mapper) {
                $this->entities = array_merge($this->entities, $mapper->getClassNames());
            }
            $this->entities = array_unique($this->entities);

            $output->writeln(sprintf('<comment>%d entities found to index (through %s mappers)</comment>', count($this->entities), count($mappers)));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->entities as $entity) {
            if (\function_exists('memory_reset_peak_usage')) {
                \memory_reset_peak_usage();
            }

            /** @var EntityManagerInterface $em */
            $em = $this->doctrine->getManager($input->getOption('em'));

            $reflection = $em->getClassMetadata($entity)->getReflectionClass();
            $entity = $reflection->name;
            $output->writeln(['', sprintf('<info>%s</info>', $entity)]);

            if (null !== ($repository = $this->solrManager->getRepository($entity))) {
                if ($repository instanceof WrappedSearchDocumentRepository) {
                    $repository->setSourceRepository($em->getRepository($entity));
                }
            } else {
                $repository = $em->getRepository($entity);

                if (!$repository instanceof SearchDocumentRepository) {
                    $repository = new SearchDocumentRepositoryAdapter($repository);
                }
            }

            if ($input->getOption('debug')) {
                $this->doctrine
                    ->getConnection()
                    ->getConfiguration()
                    ->setSQLLogger(new EchoSQLLogger());
            }
            $output->writeln('Finding indexable items...');

            $records = $repository->findIndexableDocuments(
                $input->getOption('where'),
                $input->getOption('limit'),
                $input->getOption('offset')
            );

            $total = count($records);
            $output->writeln(sprintf('<comment>%s items found.</comment>', $total));

            $output->writeln('Reindexing items...');

            $startTime = microtime(true);
            if ($reflection->implementsInterface(Extractable::class)) {
                [$extractableRecords, $updatableRecords] = $this->splitRecords($records);
                [$n, $i] = $this->extractBatch($input, $output, $extractableRecords);
                $output->write("\n");
                $output->writeln(sprintf('Processed (Extracted) %s of %s items.', $i, $n));

                if (count($updatableRecords)) {
                    [$n, $i] = $this->updateBatch($input, $output, $updatableRecords);
                    $output->write("\n");
                    $output->writeln(sprintf('Processed (Updated) %s of %s items.', $i, $n));
                }
            } else {
                [$n, $i] = $this->updateBatch($input, $output, $records);
            }
            $duration = round($duration = microtime(true) - $startTime, $duration < 4 ? 2 : ($duration < 32 ? 1 : 0));
            $output->writeln(sprintf('<comment>Processed %s of %s items in %s seconds. Peak mem usage: %2d Mb</comment>', $i, $n, $duration, \memory_get_peak_usage() / 1048576));
            $em->clear();
            gc_collect_cycles();
        }

        return 0;
    }

    /**
     * @param array|Collection $records
     * @return array
     */
    private function updateBatch(InputInterface $input, OutputInterface $output, $records)
    {
        $total = count($records);
        $progress = new ProgressBar($output instanceof StreamOutput ? new StreamOutput($output->getStream()) : $output, $total);
        $progress->display();
        [$n, $i] = $this->solrManager->updateBatch(
            $records,
            function ($n) use ($progress, $total, $output) {
                $progress->setProgress($n);

                if ($n === $total) {
                    $progress->finish();
                    $output->write("\n");
                    $size = $this->solrManager->update ? $this->solrManager->update->getQueryByteSize() : 0;
                    $output->writeln(sprintf('Sending data (%s)...', $size >= 1048576 ? sprintf('%0.1f Mb', $size / 1048576) : sprintf('%0.2f Kb', $size / 1024)));
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

        return [$n, $i];
    }

    /**
     * @param array|Collection $records
     * @return array
     */
    private function extractBatch(InputInterface $input, OutputInterface $output, $records)
    {
        $total = count($records);
        $progress = new ProgressBar($output instanceof StreamOutput ? new StreamOutput($output->getStream()) : $output, $total);
        $progress->display();
        [$n, $i] = $this->solrManager->extractBatch(
            $records,
            function ($n) use ($progress, $total, $output) {
                $progress->setProgress($n);

                if ($n === $total) {
                    $progress->finish();
                    $output->write("\n");
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

        return [$n, $i];
    }

    /**
     * Split the records to being processed by an Update or Extract query
     */
    private function splitRecords(array $records): array
    {
        $extractableRecords = array_filter($records, [$this, 'hasResource']);
        $updatableRecords = array_filter($records, [$this, 'hasNoResource']);

        return [$extractableRecords, $updatableRecords];
    }

    /** Defines whether a Extractable has a resource. */
    public function hasResource(Extractable $extractable): bool
    {
        return is_resource($extractable->getFileResource());
    }

    /** Defines whether a Extractable has not a resource. */
    public function hasNoResource(Extractable $extractable): bool
    {
        return !is_resource($extractable->getFileResource());
    }
}
