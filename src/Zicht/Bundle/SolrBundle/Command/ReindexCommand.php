<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\ORM\EntityManager;
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
use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\SolrManager;

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
            ->addOption('delete-first', 'd', InputOption::VALUE_NONE, 'Delete the document from solr before updating')

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

//    private function getTotals($repos, $where, $limit, $offset)
//    {
//        if ('' === $limit || null === $limit) {
//            $total = $repos->countIndexableDocuments($where);
//            if ('' !== $offset && null !== $offset) {
//                $total -= $offset;
//            }
//        } else {
//            $total = $limit;
//        }
//    }

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
        $where = $input->getOption('where');
        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        foreach ($this->entities as $entity) {

            $output->writeln(sprintf('Processing "<info>%s</info>"', $entity));
            $meta = $this->solrManager->getDocumentMapperMetadata($entity);

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

            foreach ($repos->findIndexableDocuments($where, $limit, $offset) as $record) {
                $manager->detach($record);
                $progress->advance(1);

                var_dump($this->solrManager->map($meta, $record));exit;

            }

            $progress->finish();
            $output->write("\n");

//            var_dump($repos->countIndexableDocuments($input->getOption('where')));exit;

//            $records = $repos->findIndexableDocuments(
//                $input->getOption('where'),
//                $input->getOption('limit'),
//                $input->getOption('offset')
//            );
//
//            $total = $records->;

        }

//        /** @var $solrManager \Zicht\Bundle\SolrBundle\Manager\SolrManager */
//
//        $output->writeln("Preparing entities ...");
//
//        $em = $this->doctrine->getManager($input->getOption('em'));
//
//        $entity = $em->getClassMetadata($input->getArgument('entity'))->getReflectionClass()->name;
//
//        if (null !== ($repos = $this->solrManager->getRepository($entity))) {
//            if ($repos instanceof WrappedSearchDocumentRepository) {
//                $repos->setSourceRepository($em->getRepository($entity));
//            }
//        } else {
//            $repos = $em->getRepository($entity);
//
//            if (!$repos instanceof SearchDocumentRepository) {
//                $repos = new SearchDocumentRepositoryAdapter($repos);
//            }
//        }
//
//        if ($input->getOption('debug')) {
//            $this->doctrine
//                ->getConnection()
//                ->getConfiguration()
//                ->setSQLLogger(new EchoSQLLogger())
//            ;
//        }
//
//        $output->writeln("Finding indexable documents...");
//
//        $records = $repos->findIndexableDocuments(
//            $input->getOption('where'),
//            $input->getOption('limit'),
//            $input->getOption('offset')
//        );
//
//        $total = count($records);
//
//        $output->writeln("Reindexing records ...");
//        $progress = new ProgressBar($output, $total);
//        $progress->display();
//        list($n, $i) = $this->solrManager->updateBatch(
//            $records,
//            function($n) use($progress, $total, $output) {
//                $progress->setProgress($n);
//                if ($n == $total) {
//                    $progress->finish();
//                    $output->write("\n");
//                    $output->writeln("Flushing ...");
//                }
//            },
//            function($record, $e) use($input, $output) {
//                if (!$input->getOption('debug')) {
//                    $output->write(sprintf("\nError indexing record: %s (%s)\n", (string)$record, $e->getMessage()));
//                } else {
//                    throw $e;
//                }
//            },
//            (bool)$input->getOption('delete-first')
//        );
//        $output->write("\n");
//        $output->writeln("Processed $i of $n items. Peak mem usage: " . sprintf('.%2d Mb', memory_get_peak_usage() / 1024 / 1024));
    }
}