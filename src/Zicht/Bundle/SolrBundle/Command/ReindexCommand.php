<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use \Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepositoryAdapter;
use \Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;

/**
 * Reindex a specified repository or entity in SOLR
 */
class ReindexCommand extends ContainerAwareCommand
{
    /** @var array  */
    protected $entities = [];

    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:reindex')
            ->addArgument('entity', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, 'The entity class(es) to fetch records from')
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
    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        $this->entities = $input->getArgument('entity');

        if (empty($this->entities)) {
            $solr = $this->getContainer()->get('zicht_solr.manager');
            $metaCollection = $this
                ->getContainer()
                ->get('doctrine')
                ->getManager()
                ->getMetadataFactory()
                ->getAllMetadata();

            /** @var \Doctrine\ORM\Mapping\ClassMetadata $meta */
            foreach ($metaCollection as $meta) {
                if (!$meta->reflClass->isAbstract() && !in_array($meta->getName(), $this->entities) && $solr->support($meta->newInstance())) {
                    foreach ($this->entities as $entity) {
                        if (is_a($meta->getName(), $entity, true)) {
                            continue 2;
                        }
                    }
                    $this->entities[] = $meta->getName();
                }
            }
        }
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        /** @var $solr \Zicht\Bundle\SolrBundle\Manager\SolrManager */
        $solr = $this->getContainer()->get('zicht_solr.manager');
        $solr->disableTimeout();

        foreach ($this->entities as $entity) {
            $output->writeln(sprintf("<info>Processing entity %s</info>", $entity));
            $repos = $this->getContainer()->get('doctrine')->getManager($input->getOption('em'))->getRepository($entity);

            if (!$repos instanceof SearchDocumentRepository) {
                $repos = new SearchDocumentRepositoryAdapter($repos);
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
            if ($total > 40) {
                $progress->setRedrawFrequency($total / 40);
            }
            $progress->display();
            list($n, $i) = $solr->updateBatch(
                $records,
                function ($n) use ($progress, $total) {
                    $progress->setProgress($n);
                },
                function ($record, $e) use ($input, $output, $progress) {
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
            $output->writeln("Processed $i of $n items");
        }
    }
}
