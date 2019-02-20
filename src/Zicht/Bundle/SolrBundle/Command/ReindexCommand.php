<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Doctrine\ORM\BaseQueryBuilderRepositoryInterface;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;
use Zicht\Bundle\SolrBundle\Mapping\DocumentRepositoryInterface;
use Zicht\Bundle\SolrBundle\Doctrine\ORM\EntityRepositoryWrapper;
use Zicht\Bundle\SolrBundle\QueryBuilder\Update;
use Zicht\Bundle\SolrBundle\Service\SolrManager;

/**
 * Reindex a specified repository or entity in SOLR
 */
class ReindexCommand extends Command
{
    /** @var array  */
    private $entities;
    /** @var SolrManager */
    private $solrManager;
    /** @var Registry  */
    private $doctrine;

    /**
     * Setup the reindex command
     *
     * @param SolrManager $solrManager
     * @param Registry $doctrine
     */
    public function __construct(SolrManager $solrManager, Registry $doctrine)
    {
        parent::__construct();

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
            ->addArgument('entities', InputArgument::IS_ARRAY, 'The entity class or classes to to reindex.')
            ->addOption('where', 'w', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'An optional where clause to pass to the query builder. The entity\'s query alias is "d" (as in document), so you need to pass criteria such as \'d.dateCreated > CURDATE()\'. This only works when no custom repository is defined on the entity or when the custom repository extends the EntityRepositoryWrapper.')
            ->addOption('limit', 'l', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The LIMIT clause to facilitate paging (chunks) of indexing (number of items per chunk)')
            ->addOption('offset', 'o', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The OFFSET clause to facilitate paging (chunks) of indexing (offset to start the chunk at)')
            ->addOption('batch-size', 'b', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The batch size when to flush solr.', 200)
            ->addOption('no-children', '', InputOption::VALUE_NONE, 'This will when no entities provided only reindex the parent entities.')
            ->addOption('debug', '', InputOption::VALUE_NONE, 'Debug: i.e. don\'t catch exceptions while indexing')
            ->setDescription('Reindexes entities in the SOLR index');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $metadataFactory = $this->solrManager->getDocumentMapperMetadataFactory();

        if ([] === $this->entities = $input->getArgument('entities')) {
            $this->entities = iterator_to_array($metadataFactory->getEntities(true));
        }

        if (false === $input->getOption('no-children')) {
            $this->entities = array_merge($this->entities, ...array_filter(array_map([$metadataFactory, 'getChildrenOf'], $this->entities)));
        }

        if ($input->getOption('debug')) {
            $this->doctrine
                ->getConnection()
                ->getConfiguration()
                ->setSQLLogger(new EchoSQLLogger());
        }
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param ObjectManager $manager
     * @return DocumentRepositoryInterface
     */
    private function getRepository(DocumentMapperMetadata $meta, ObjectManager $manager)
    {
        if (null === $repo = $this->solrManager->getRepository($meta->getClassName())) {
            $repo = $manager->getRepository($meta->getClassName());

            if (!$repo instanceof DocumentRepositoryInterface) {
                $repo = new EntityRepositoryWrapper($repo);
            }
        }
        return $repo;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $where = $input->getOption('where');
        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');
        $batch =  $input->getOption('batch-size');
        $index = 0;
        $update = new Update();

        foreach ($this->entities as $entity) {
            $output->writeln(sprintf('Processing "<info>%s</info>"', $entity));
            $meta = $this->solrManager->getDocumentMapperMetadata($entity);

            if (!$meta->isActive()) {
                $output->writeln('<comment>Mapper is disabled, skipping indexing</comment>');
                continue;
            }

            /** @var $manager ObjectManager */
            if (null === $manager = $this->doctrine->getManagerForClass($entity)) {
                throw new \RuntimeException('Could not find a ObjectManager for class "' . $entity . '"');
            }

            $repos = $this->getRepository($meta, $manager);
            $total = $repos->getDocumentsCount($limit, $offset);

            if (!empty($where)) {
                if ($repos instanceof BaseQueryBuilderRepositoryInterface) {
                    foreach ($where as $condition) {
                        $repos->getBaseQueryBuilder()->andWhere($condition);
                    }
                } else {
                    $output->write(sprintf('<comment>repository %s does not support the `where` filter (should implement "%s")</comment>', get_class($repos), BaseQueryBuilderRepositoryInterface::class));
                }
            }

            if ($total > 0) {
                $output->writeln("Reindexing {$total} records");
            } else {
                $output->writeln('Reindexing records...');
            }

            $progress = new ProgressBar($output, $total);
            $progress->display();

            foreach ($repos->getDocuments($limit, $offset) as $record) {
                $progress->advance(1);
                $index++;
                $this->solrManager->updateEntity($update, $record);
                $repos->free($record);
                if ($index > 0 && 0 === $index % $batch) {
                    $update->commit();
                    $this->solrManager->persis($update);
                    $update->reset();
                }
            }

            $progress->finish();
            $progress->clear();
            $output->writeln('finished');
        }

        if (0 !== $index % $batch) {
            $update->commit();
            $this->solrManager->persis($update);
        }

        $output->writeln(sprintf('Total time: %.02fs, Peak mem usage: %.02fMB', microtime(true)-$start, memory_get_peak_usage()/1024/1024));
    }
}
