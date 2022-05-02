<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command\Managed;

use Symfony\Component\Console;
use Zicht\Bundle\SolrBundle\Command\AbstractCommand;
use Zicht\Bundle\SolrBundle\Manager\SynonymManager;
use Zicht\Bundle\SolrBundle\Solr\Client;

class SynonymsListCommand extends AbstractCommand
{
    /** @var SynonymManager */
    protected $manager;

    public function __construct(Client $solr, SynonymManager $manager)
    {
        parent::__construct($solr);

        $this->manager = $manager;
    }

    /** {@inheritDoc} */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:synonyms-list')
            ->setDescription('List solr synonyms')
            ->addArgument('managed', Console\Input\InputArgument::REQUIRED, 'Specify the managed prefix')
        ;
    }

    /** {@inheritDoc} */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $output->writeln('Synonyms registered in SOLR:');
        $output->writeln('');

        foreach ($this->manager->findAll($input->getArgument('managed')) as $key => $value) {
            $output->writeln(sprintf('- %s', $key));

            foreach ($value as $item) {
                $output->writeln(sprintf('  - %s', $item));
            }
        }

        return 0;
    }
}