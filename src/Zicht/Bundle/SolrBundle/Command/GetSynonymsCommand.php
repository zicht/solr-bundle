<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console;
use Zicht\Bundle\SolrBundle\Entity\Synonym;
use Zicht\Bundle\SolrBundle\Manager\StopWordManager;
use Zicht\Bundle\SolrBundle\Manager\SynonymManager;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * Class GetSynonymsCommand
 */
class GetSynonymsCommand extends AbstractCommand
{
    /**
     * @param Client $solr
     */
    public function __construct(Client $solr, SynonymManager $manager)
    {
        parent::__construct($solr);

        $this->manager = $manager;
    }

    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:list-synonyms')
            ->setDescription('List solr synonyms')
            ->addArgument('managed', Console\Input\InputArgument::REQUIRED, 'Specify the managed prefix')
        ;
    }

    /**
     * @{inheritDoc}
     */
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
    }
}