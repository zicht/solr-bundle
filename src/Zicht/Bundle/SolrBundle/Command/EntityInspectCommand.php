<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Doctrine\ORM\BaseQueryBuilderRepositoryInterface;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;
use Zicht\Bundle\SolrBundle\Mapping\IdGeneratorDefault;
use Zicht\Bundle\SolrBundle\Mapping\MethodMergeMapper;
use Zicht\Bundle\SolrBundle\Mapping\RepositoryTrait;
use Zicht\Bundle\SolrBundle\Service\SolrManager;

/**
 * Class EntityInspectCommand
 * @package Zicht\Bundle\SolrBundle\Command
 */
class EntityInspectCommand extends Command
{
    use RepositoryTrait;

    /** @var SolrManager */
    private $manager;
    /** @var Registry  */
    private $doctrine;

    /**
     * EntityInspectCommand constructor.
     *
     * @param SolrManager $manager
     */
    public function __construct(SolrManager $manager, Registry $doctrine)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->doctrine = $doctrine;
    }


    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:inspect-entity')
            ->addArgument('entity', InputArgument::REQUIRED)
            ->addOption('dump', 'd', InputOption::VALUE_NONE, 'Create a mapping and dump the result')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'Id to print')
            ->setDescription('Print debug mapping information from the given entity');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $meta = $this->manager->getDocumentMapperMetadata($input->getArgument('entity'))) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a registered entity.'));
        }

        $table = new Table($output);
        $this->renderClassInfo($meta, $table);
        $this->renderMapping($meta, $table);
        $this->renderOptions($meta, $table);
        $table->setStyle($this->getTableStyle());
        $table->render();
        $this->dumpEntity($meta, $input, $output);
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param OutputInterface $output
     */
    private function dumpEntity(DocumentMapperMetadata $meta, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('dump') || $input->getOption('id')) {
            $repo = $this->getRepository($meta, $this->doctrine->getManagerForClass($meta->getClassName()), $this->manager);

            if ($input->getOption('id') && $repo instanceof BaseQueryBuilderRepositoryInterface) {
                $repo->getBaseQueryBuilder()->andWhere(sprintf('%s.id = %d', $repo->getBaseQueryBuilder()->getRootAliases()[0], $input->getOption('id')));
                $entity = $repo->getDocuments(1)->current();
            } else {
                $entity = $repo->getDocuments(1, rand(1, $repo->getDocumentsCount()))->current();
            }

            $output->writeln('');
            $output->writeln(sprintf('<fg=cyan;options=bold>Document dump (%d)</>', $this->entity->getId()));
            $method = new \ReflectionMethod($this->manager, 'marshall');
            $method->setAccessible(true);
            $output->writeln(\json_encode($method->invoke($this->manager, $meta, $entity), JSON_PRETTY_PRINT));

        }
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param Table $table
     */
    private function renderClassInfo(DocumentMapperMetadata $meta, Table $table)
    {
        $table->addRow([new TableCell('<fg=cyan;options=bold>Class Name</>', ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell($meta->getClassName(), ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell('<fg=cyan;options=bold>Id Field</>', ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell(sprintf('%s::$%s', ...$meta->getIdField()), ['colspan' => 2])]);
        $table->addRow(new TableSeparator());

        if (null === $generator = $meta->getIdGenerator()) {
            $generator = IdGeneratorDefault::class;
        }

        $table->addRow([new TableCell('<fg=cyan;options=bold>Id Generator</>', ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell($generator, ['colspan' => 2])]);

        if ($meta->getOption('child_inheritance')) {
            if (null !== $children = $this->manager->getDocumentMapperMetadataFactory()->getChildrenOf($meta->getClassName())) {
                $table->addRow(new TableSeparator());
                $table->addRow([new TableCell('<fg=cyan;options=bold>Child Entities</>', ['colspan' => 2])]);
                $table->addRow(new TableSeparator());
                foreach ($children as $name) {
                    $table->addRow([new TableCell($name, ['colspan' => 2])]);
                }
            }
        }

        if (null !== $repository = $meta->getRepository()) {
            $table->addRow(new TableSeparator());
            $table->addRow([new TableCell('<fg=cyan;options=bold>Repository</>', ['colspan' => 2])]);
            $table->addRow(new TableSeparator());
            $table->addRow([new TableCell($repository, ['colspan' => 2])]);
        }
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param Table $table
     */
    private function renderMapping(DocumentMapperMetadata $meta, Table $table)
    {
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell('<fg=cyan;options=bold>Field mapping</>', ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $mappings = $meta->getMapping();
        usort($mappings, function($a, $b) {
            if ($a->getName() == $b->getName()) {
                return 0;
            }
            return ($a->getName() < $b->getName()) ? -1 : 1;
        });
        foreach ($mappings as $mapper) {
            if ($mapper instanceof MethodMergeMapper) {
                $table->addRow(['...', (string)$mapper]);
            } else {
                $table->addRow([$mapper->getName(), (string)$mapper]);
            }
        }
        $transformers = array_keys(iterator_to_array($meta->getTransformers()));
        if (count($transformers) > 0) {
            $table->addRow(new TableSeparator());
            $table->addRow([new TableCell('<fg=cyan;options=bold>Field Transformers</>', ['colspan' => 2])]);
            $table->addRow(new TableSeparator());
            foreach ($transformers as $name) {
                foreach ($meta->getTransformers($name) as $list) {
                    foreach ($list as $transformer) {
                        $table->addRow([$name, $transformer]);
                    }
                }
            }
        }
    }

    /**
     * @param DocumentMapperMetadata $meta
     * @param Table $table
     */
    private function renderOptions(DocumentMapperMetadata $meta, Table $table)
    {
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell('<fg=cyan;options=bold>Options</>', ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        foreach ($meta->getOptions() as $name => $option) {
            switch ($name) {
                case 'child_inheritance':
                    $table->addRow(['child_inheritance', ($option) ? 'yes' : 'no']);
                    break;
            }
        }

        if (null !== $events = $meta->getOption('events')) {
            $table->addRow(new TableSeparator());
            $last = @end(array_keys($events));
            foreach ($events as $name => $class) {
                $table->addRow([new TableCell('<fg=cyan;options=bold>events_' . $name . '</>', ['colspan' => 2])]);
                $table->addRow(new TableSeparator());
                foreach ($class as $className) {
                    $table->addRow([new TableCell($className, ['colspan' => 2])]);
                }
                if ($last !== $name) {
                    $table->addRow(new TableSeparator());
                }
            }
        }

        if (count($meta->getParams()) > 0) {
            $table->addRow(new TableSeparator());
            $table->addRow([new TableCell('<fg=cyan;options=bold>Parameters</>', ['colspan' => 2])]);
            $table->addRow(new TableSeparator());
            foreach ($meta->getParams() as $name => $value) {
                $table->addRow([$name, $value]);
            }
        }
    }

    /**
     * @return TableStyle
     */
    private function getTableStyle()
    {
        return (new TableStyle())
            ->setHorizontalBorderChar('-')
            ->setVerticalBorderChar(' ')
            ->setCrossingChar('-')
            ->setCellHeaderFormat('%s');
    }
}
