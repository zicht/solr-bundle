<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command\Managed;

use Symfony\Component\Console;
use Zicht\Bundle\SolrBundle\Command\AbstractCommand;
use Zicht\Bundle\SolrBundle\Manager\StopWordManager;
use Zicht\Bundle\SolrBundle\Solr\Client;

class StopWordsListCommand extends AbstractCommand
{
    public function __construct(Client $solr, StopWordManager $manager)
    {
        parent::__construct($solr);

        $this->manager = $manager;
    }

    /** {@inheritDoc} */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:stop-words-list')
            ->setDescription('List solr stop words')
            ->addArgument('managed', Console\Input\InputArgument::REQUIRED, 'Specify the managed prefix')
        ;
    }

    /** {@inheritDoc} */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $output->writeln('Stopwords registered in SOLR:');
        $output->writeln('');

        foreach ($this->manager->findAll($input->getArgument('managed')) as $item) {
            $output->writeln(sprintf('- %s', $item));
        }

        return 0;
    }
}