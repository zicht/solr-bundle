<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

class SelectCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:solr:select')
            ->setDescription('Do a select (search) query in SOLR')
            ->addArgument('query', InputArgument::OPTIONAL, "Select these documents (e.g.: 'id:abc')", '*:*')
            ->addOption('field', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Fields to display. Defaults to all fields and score')
            ->addOption('deftype', null, InputOption::VALUE_REQUIRED, 'Set the `defType` of the query, e.g. `edismax`')
            ->addOption('qf', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Specify a `qf` (query fields) parameter for when the deftype is set to dismax or edismax. Ignored if defType is not set')
            ->addOption('fq', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Specify a `fq` (filter query)')
            ->addOption('rows', null, InputOption::VALUE_REQUIRED, 'Specify the number of rows to return')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Specify the start of the paged results');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $select = new QueryBuilder\Select();
        $select->setQuery($input->getArgument('query'));
        if ($input->getOption('deftype')) {
            $select
                ->setDefType($input->getOption('deftype'))
                ->setQueryFields($input->getOption('qf'));
        }
        if ($fq = $input->getOption('fq')) {
            $select->setFilterQuery($fq);
        }
        if ($fl = $input->getOption('field')) {
            $select->setFieldList($fl);
        }
        if ($rows = $input->getOption('rows')) {
            $select->setRows($rows);
        }
        if ($start = $input->getOption('start')) {
            $select->setStart($start);
        }
        $results = $this->solr->select($select)->response->docs;
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(sprintf('<info>%s %s</info>', $this->solr->getLastResponse()->getReasonPhrase(), $this->solr->getLastRequest()->getUri()));
            $output->writeln(sprintf('<info>%d result(s)</info>', count($results)));
            $output->writeln('');
        }
        foreach ($results as $doc) {
            $output->writeln(json_encode($doc, JSON_PRETTY_PRINT));
        }

        return 0;
    }
}
