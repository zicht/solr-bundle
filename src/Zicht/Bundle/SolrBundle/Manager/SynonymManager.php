<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Entity\Synonym;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * Manages the synonym's registered in SOLR.
 */
class SynonymManager
{
    /**
     * @var Client
     */
    protected $client;

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
     * @return bool
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
     * Adds multiple synonyms at once
     *
     * @param Synonym[] $synonyms
     * @return bool
     */
    public function addSynonyms($synonyms)
    {
        $dataPerManaged = [];
        foreach ($synonyms as $synonym) {
            if (!$synonym instanceof Synonym) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Synonyms array elements must be of type %s. Got %s',
                        Synonym::class,
                        is_object($synonym) ? get_class($synonym) : gettype($synonym)
                    )
                );
            }
            if (!array_key_exists($synonym->getManaged(), $dataPerManaged)) {
                $dataPerManaged[$synonym->getManaged()] = [];
            }
            $dataPerManaged[$synonym->getManaged()][$synonym->getIdentifier()] = explode(PHP_EOL, str_replace("\r", '', $synonym->getValue()));
        }

        $result = array_map(
            function ($managed, $data) {
                $request = $this->client->getHttpClient()->createRequest(
                    'PUT',
                    sprintf('schema/analysis/synonyms/%s', $managed),
                    [
                        'body' => \json_encode($data)
                    ]
                );

                return 200 === $this->client->request($request)->getStatusCode();
            },
            array_keys($dataPerManaged),
            $dataPerManaged
        );

        return !in_array(false, $result, true);
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
