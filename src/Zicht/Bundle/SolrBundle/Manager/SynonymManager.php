<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Entity\Synonym;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SearchDocumentRepository;
use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

/**
 * Manages the synonym's registered in SOLR.
 */
class SynonymManager
{
    /**
     * @var Client
     */
    protected $client = null;

    /**
     * Constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Return all the synonyms managed in SOLR.
     *
     * @param $managed
     * @return array
     */
    public function findAll($managed)
    {
        $request = $this->client->getHttpClient()->createRequest('GET', sprintf('schema/analysis/synonyms/%s', $managed));
        $response = $this->client->request($request);

        if (200 === $response->getStatusCode()) {
            $data = $response->json();

            if (array_key_exists('synonymMappings', $data) && array_key_exists('managedMap', $data['synonymMappings'])) {
                return $data['synonymMappings']['managedMap'];
            }
        }

        return [];
    }

    /**
     * Adds an synonym
     *
     * @param Synonym $synonym
     * @return \GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Message\ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     */
    public function addSynonym(Synonym $synonym)
    {
        $data = explode(PHP_EOL, str_replace("\r", '', $synonym->getValue()));
        $request =  $this->client->getHttpClient()->createRequest(
            'PUT',
            sprintf('schema/analysis/synonyms/%s', $synonym->getManaged()),
            [
                'body' => \json_encode([$synonym->getIdentifier() => $data])
            ]
        );

        return 200 === $this->client->request($request)->getStatusCode();
    }

    /**
     * Has an synonym.
     *
     * @param Synonym $synonym
     * @return bool
     */
    public function hasSynonym(Synonym $synonym)
    {
        $request =  $this->client->getHttpClient()->createRequest(
            'GET',
            sprintf('schema/analysis/synonyms/%s/%s', $synonym->getManaged(), $synonym->getIdentifier())
        );

        return 200 === $this->client->request($request)->getStatusCode();
    }

    /**
     * Removes an synonym.
     *
     * @param Synonym $synonym
     * @return bool
     */
    public function removeSynonym(Synonym $synonym)
    {
        $request =  $this->client->getHttpClient()->createRequest(
            'DELETE',
            sprintf('schema/analysis/synonyms/%s/%s', $synonym->getManaged(), $synonym->getIdentifier())
        );

        return 200 === $this->client->request($request)->getStatusCode();
    }
}
