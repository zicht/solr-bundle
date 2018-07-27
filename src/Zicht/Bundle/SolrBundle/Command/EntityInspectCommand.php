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
use Zicht\Bundle\SolrBundle\Mapping\MethodMergeMapper;
use Zicht\Bundle\SolrBundle\Solr\SolrManager;

class EntityInspectCommand extends Command
{
    /** @var SolrManager */
    private $manager;
    /** @var */
    private $dispatcher;

    /**
     * EntityInspectCommand constructor.
     *
     * @param SolrManager $manager
     */
    public function __construct(SolrManager $manager, EventDispatcherInterface $dispatcher)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
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
        $table->addRow([new TableCell("<fg=cyan;options=bold>Class Name</>", ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell($meta->getClassName(), ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell("<fg=cyan;options=bold>Id Field</>", ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell(sprintf('%s::$%s', ...$meta->getIdField()), ['colspan' => 2])]);
        $table->addRow(new TableSeparator());

        $property = new \ReflectionMethod($this->manager, 'getIdGenerator');
        $property->setAccessible(true);
        $table->addRow([new TableCell("<fg=cyan;options=bold>Id Generator</>", ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow([new TableCell(get_class($property->invoke($this->manager, $meta)), ['colspan' => 2])]);

        if (!$meta->getOption('strict')) {

            $entities = $this->manager->getDocumentMapperMetadataFactory()->getEntities();

            if (isset($entities[$meta->getClassName()]) && count($entities[$meta->getClassName()]) > 0) {
                $table->addRow(new TableSeparator());
                $table->addRow([new TableCell("<fg=cyan;options=bold>Child Entities</>", ['colspan' => 2])]);
                $table->addRow(new TableSeparator());
                foreach ($entities[$meta->getClassName()] as $name) {
                    $table->addRow([new TableCell($name, ['colspan' => 2])]);
                }
            }
        }
        if (null !== $repository = $meta->getRepository()) {
            $table->addRow(new TableSeparator());
            $table->addRow([new TableCell("<fg=cyan;options=bold>Repository</>", ['colspan' => 2])]);
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
        $table->addRow([new TableCell("<fg=cyan;options=bold>Field mapping</>", ['colspan' => 2])]);
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
            $table->addRow([new TableCell("<fg=cyan;options=bold>Field Transformers</>", ['colspan' => 2])]);
            $table->addRow(new TableSeparator());

            foreach($transformers as $name) {
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
        $table->addRow([new TableCell("<fg=cyan;options=bold>Options</>", ['colspan' => 2])]);
        $table->addRow(new TableSeparator());
        $table->addRow(['active', ($meta->isActive()) ? 'yes' : 'no']);
        foreach ($meta->getOptions() as $name => $option) {
            switch ($name) {
                case 'strict':
                    $table->addRow(['strict', ($option) ? 'yes' : 'no']);
                    break;
            }
        }

        if (count($meta->getParams()) > 0) {
            $table->addRow(new TableSeparator());
            $table->addRow([new TableCell("<fg=cyan;options=bold>Parameters</>", ['colspan' => 2])]);
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