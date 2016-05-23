<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

/**
 * Reindex a specified repository or entity in SOLR
 */
class SelectCommand extends AbstractCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:select')
            ->addArgument(
                'query',
                Console\Input\InputArgument::OPTIONAL,
                "Select these documents (e.g.: 'id:abc')",
                '*:*'
            )
            ->addOption(
                'field',
                'f',
                Console\Input\InputOption::VALUE_REQUIRED | Console\Input\InputOption::VALUE_IS_ARRAY,
                "Fields to display. Defaults to all fields and score"
            )
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $select = new QueryBuilder\Select();

        $select->setQuery($input->getArgument('query'));

        if ($fl = $input->getOption('field')) {
            $select->setFieldList($fl);
        }
        foreach ($this->solr->select($select)->response->docs as $doc) {
            $output->writeln(json_encode($doc, JSON_PRETTY_PRINT));
        }
    }
}