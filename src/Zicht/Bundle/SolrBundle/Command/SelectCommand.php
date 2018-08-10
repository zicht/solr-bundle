<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Psr\Http\Message\StreamInterface;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Console\Output\StreamableOutputWrapper;
use Zicht\Bundle\SolrBundle\Event\HttpClientResponseEvent;
use Zicht\Bundle\SolrBundle\QueryBuilder;
use Zicht\Http\Handler\HandlerDebugInterface;
use Zicht\Http\Stream\ResourceStream;

/**
 * Reindex a specified repository or entity in SOLR
 */
class SelectCommand extends AbstractCommand
{
    /** @var bool  */
    private $isVerbose = false;
    /** @var OutputInterface  */
    private $output;
    /** @var StreamInterface|null  */
    private $stream;

    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:select')
            ->setDescription('Do a select (search) query in SOLR')
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
                'Fields to display. Defaults to all fields and score'
            )
            ->addOption(
                'deftype',
                '',
                Console\Input\InputOption::VALUE_REQUIRED,
                'Set the `defType` of the query, e.g. `edismax`'
            )
            ->addOption(
                'qf',
                '',
                Console\Input\InputOption::VALUE_REQUIRED | Console\Input\InputOption::VALUE_IS_ARRAY,
                'Specify a `qf` (query fields) parameter for when the deftype is set to dismax or edismax. Ignored if defType is not set'
            )
            ->addOption(
                'fq',
                '',
                Console\Input\InputOption::VALUE_REQUIRED | Console\Input\InputOption::VALUE_IS_ARRAY,
                'Specify a `fq` (filter query)'
            )
            ->addOption(
                'rows',
                '',
                Console\Input\InputOption::VALUE_REQUIRED,
                'Specify the number of rows to return'
            )
            ->addOption(
                'start',
                '',
                Console\Input\InputOption::VALUE_REQUIRED,
                'Specify the start of the paged results'
            );
    }

    /**
     * @return array
     */
    private function getStreamFmt()
    {
        return [
            '/^\*\s.*$/' => '<comment>\0</comment>',
            '/^>>.*$/' => '<info>\0</info>',
        ];
    }

    /**
     * @{inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if (false !== $this->isVerbose = $output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $this->output = $output;
            $handler = $this->solr->getClient()->getHandler();
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE && $handler instanceof HandlerDebugInterface) {
                $this->stream = new ResourceStream(StreamableOutputWrapper::getResource($output, $this->getStreamFmt()));
                $handler->setDebug($this->stream);
            }
        }
    }

    /**
     * @param HttpClientResponseEvent $event
     */
    public function onResponse(HttpClientResponseEvent $event)
    {
        if ($this->isVerbose) {
            if (!$this->stream instanceof StreamInterface) {
                $this->output->writeln('request uri: ' . (string)$event->getRequest()->getUri());
            }
        }
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $select = new QueryBuilder\Select();
        $select->setQuery($input->getArgument('query'));

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $select->addParam('indent', 'true');
        }

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

        $response = $this->solr->select($select);

        if ($output->getVerbosity() <= OutputInterface::VERBOSITY_VERY_VERBOSE || !$this->stream instanceof StreamInterface) {
            foreach ($response->response->docs as $doc) {
                $output->writeln(json_encode($doc, JSON_PRETTY_PRINT));
            }
        }
    }
}
