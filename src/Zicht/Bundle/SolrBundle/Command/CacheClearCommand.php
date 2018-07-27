<?php
namespace Zicht\Bundle\SolrBundle\Command;

use Psr\Http\Message\StreamInterface;
use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\SolrBundle\Console\Output\StreamableOutputWrapper;
use Zicht\Bundle\SolrBundle\Event\HttpClientResponseEvent;
use Zicht\Bundle\SolrBundle\QueryBuilder;
use Zicht\Bundle\SolrBundle\Service\SolrManager;
use Zicht\Http\Handler\HandlerDebugInterface;
use Zicht\Http\Stream\ResourceStream;

class CacheClearCommand extends Command
{
    /** @var SolrManager  */
    protected $solr;

    public function __construct(SolrManager $solr)
    {
        parent::__construct();
        $this->solr = $solr;
    }
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:solr:cache-clear')
            ->setDescription('Remove all solr (metadata) cache')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        if ($this->solr->getDocumentMapperMetadataFactory()->getCacheImpl()->clear()) {
            $output->writeln('<info>cleared metadata cache</info>');
        } else {
            $output->writeln('<error>failed to wipe metadata cache<error>');
        }
    }
}