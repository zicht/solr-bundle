<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadata;
use Zicht\Bundle\SolrBundle\Mapping\IdGeneratorDefault;
use Zicht\Bundle\SolrBundle\Mapping\MethodMergeMapper;
use Zicht\Bundle\SolrBundle\Service\SolrManager;

/**
 * Class EntityInspectCommand
 * @package Zicht\Bundle\SolrBundle\Command
 */
class EntityInspectCommand extends Command
{
    /** @var SolrManager */
    private $manager;

    /**
     * EntityInspectCommand constructor.
     *
     * @param SolrManager $manager
     */
    public function __construct(SolrManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }


    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:inspect-entity')
            ->addArgument('entity', InputArgument::REQUIRED)
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

        foreach ($meta->getMapping() as $mapper) {
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
        $table->addRow(['active', ($meta->isActive()) ? 'yes' : 'no']);
        foreach ($meta->getOptions() as $name => $option) {
            switch ($name) {
                case 'child_inheritance':
                    $table->addRow(['child_inheritance', ($option) ? 'yes' : 'no']);
                    break;
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
