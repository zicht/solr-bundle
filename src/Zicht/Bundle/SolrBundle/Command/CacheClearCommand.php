<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Zicht\Bundle\SolrBundle\Service\SolrManager;

/**
 * Class CacheClearCommand
 *
 * @package Zicht\Bundle\SolrBundle\Command
 */
class CacheClearCommand extends Command
{
    /** @var SolrManager  */
    protected $solr;

    /**
     * CacheClearCommand constructor.
     * @param SolrManager $solr
     */
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
            ->setDescription('Remove all solr (metadata) cache');
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
