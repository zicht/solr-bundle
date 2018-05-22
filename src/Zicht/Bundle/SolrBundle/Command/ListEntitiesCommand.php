<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Solr\SolrManager;


class ListEntitiesCommand extends Command
{
    /** @var SolrManager  */
    private $manager;

    /**
     * ListEntitiesCommand constructor.
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
            ->setName('zicht:solr:list-entities')
            ->setDescription('List all entities that are linked to a solr document.');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("");


        foreach($this->manager->getDocumentMapperMetadataFactory()->getEntities() as $parent => $children) {
            $output->writeln("<info>●</info> " . $parent);

            foreach ($children as $index => $child) {
                if ($index === count($children) - 1) {
                    $output->writeln("<info>└── </info>" . $child);
                } else {
                    $output->writeln("<info>├── </info>" . $child);
                }

            }

            $output->writeln("");
        }
    }
}