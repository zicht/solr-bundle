<?php
/**
 * @author    Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Builder;

use Zicht\Bundle\SolrBundle\Builder\Solarium\SolariumInterface;

/**
 * UnitOfWork for SOLR indexing tasks
 */
class Indexer
{
    protected $solr = null;

    /** @var Solarium_Query_Update */
    protected $update;
    protected $workCount = 0;

    public function __construct($client)
    {
        $this->solr = $client;
        $this->init();
    }

    function init()
    {
        $this->update    = $this->solr->createUpdate();
        $this->workCount = 0;
    }

    public function addDelete($delete)
    {
        $this->update->addDeleteQuery($delete);
        $this->workCount++;
    }

    public function addDocument($document)
    {
        $this->update->addDocument($document);
        $this->workCount++;
    }

    public function addDocumentBuilder(SolariumInterface $builder)
    {
        if ($document = $builder->createDocument($this->update)) {
            $this->addDocument($document);
        }
    }

    public function flush()
    {
        $status = null;

        if ($this->workCount > 0) {
            $this->update->addCommit();

            $result = $this->solr->update($this->update);

            switch ($result->getResponse()->getStatusCode()) {
                case 200:
                    $status = 'Total of ' . $this->workCount . ' solr jobs completed';
                    break;
                default:
                    $status = 'Indexing failed with status code: ' . $result->getResponse()->getStatusCode();
                    break;
            }

            // in case more is coming:
            $this->init();
        }
        return $status;
    }
}