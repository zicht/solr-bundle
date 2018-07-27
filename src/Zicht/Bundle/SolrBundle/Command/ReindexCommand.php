<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepositoryAdapter;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\WrappedSearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;
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
            ->addOption('where', 'w', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'An optional where clause to pass to the query builder. The entity\'s query alias is "d" (as in document), so you need to pass criteria such as \'d.dateCreated > CURDATE()\'')
            ->addOption('limit', 'l', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The LIMIT clause to facilitate paging (chunks) of indexing (number of items per chunk)')
            ->addOption('offset', 'o', InputArgument::OPTIONAL | InputOption::VALUE_REQUIRED, 'The OFFSET clause to facilitate paging (chunks) of indexing (offset to start the chunk at)')
            ->addOption('no-children', '', InputOption::VALUE_NONE, 'This will when no entities provided only reindex the parent entities.')
            ->addOption('debug', '', InputOption::VALUE_NONE, 'Debug: i.e. don\'t catch exceptions while indexing')
            ->setDescription('Reindexes entities in the SOLR index')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ([] === $this->entities = $input->getArgument('entities')) {
            $entities = $this->solrManager->getDocumentMapperMetadataFactory()->getEntities();
            if ($input->getOption('no-children')) {
                $this->entities = array_keys($entities);
            } else {
                $this->entities = array_merge(array_keys($entities), ...array_values($entities));
            }
        }

        if ($input->getOption('debug')) {
            $this->doctrine
                ->getConnection()
                ->getConfiguration()
                ->setSQLLogger(new EchoSQLLogger())
            ;
        }
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param ObjectManager $manager
     *
     * @return EntityRepository|string|SearchDocumentRepositoryAdapter
     */
    private function getRepository(DocumentMapperMetadata $meta, ObjectManager $manager)
    {
        if (null !== $repos = $this->solrManager->getRepository($meta->getClassName())) {

            if ($repos instanceof WrappedSearchDocumentRepository) {
                $repos->setSourceRepository($manager->getRepository($meta->getClassName()));
            }

        } else {
            /** @var EntityRepository $repos */
            $repos = $manager->getRepository($meta->getClassName());

            if (!$repos instanceof SearchDocumentRepository) {
                $repos = new SearchDocumentRepositoryAdapter($repos);
            }
        }
        return $repos;
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

        foreach ($this->entities as $entity) {
            $output->writeln(sprintf('Processing "<info>%s</info>"', $entity));
            $meta = $this->solrManager->getDocumentMapperMetadata($entity);

            if (!$meta->isActive()) {
                $output->writeln("<comment>Mapper is disabled, skipping indexing</comment>");
                continue;
            }

            if (null === $manager = $this->doctrine->getManagerForClass($entity)) {
                throw new \RuntimeException('Could not find a ObjectManager for class "' . $entity . '"');
            }

            $repos = $this->getRepository($meta, $manager);

            if ('' === $limit || null === $limit) {
                $total = $repos->getCountIndexableDocuments($where);
                if ('' !== $offset && null !== $offset) {
                    $total -= $offset;
                }
            } else {
                $total = $limit;
            }

            $output->writeln("Reindexing records {$total} ...");
            $progress = new ProgressBar($output, $total);
            $progress->display();
            $batch = 50;
            $index = 0;
            $update = new Update();

            foreach ($repos->findIndexableDocuments($where, $limit, $offset) as $record) {
                $manager->detach($record);
                $progress->advance(1);
                $index++;
                $update->add($this->solrManager->map($meta, $record));
                if ($index > 0 && $index%$batch === 0) {
                    $update->commit();
                    $this->solrManager->getClient()->update($update);
                    $update->reset();
                }
            }

            $this->solrManager->getClient()->update($update);
            $progress->finish();
            $output->write("\n");
        }


        $output->writeln(sprintf("Total time: %.02fs, Peak mem usage: %.02fMB", microtime(true)-$start, memory_get_peak_usage()/1024/1024));
    }
}