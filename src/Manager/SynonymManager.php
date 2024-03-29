<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use GuzzleHttp\Psr7\Request;
use Zicht\Bundle\SolrBundle\Entity\Synonym;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * Manages the synonym's registered in SOLR.
 */
class SynonymManager
{
    /** @var Client */
    protected $client;

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
     * @param string $managed
     * @return array
     */
    public function findAll($managed)
    {
        $request = new Request('GET', sprintf('%sschema/analysis/synonyms/%s', $this->client->getHttpClient()->getConfig('base_uri'), $managed));
        $response = $this->client->request($request);

        if ($response && 200 === $response->getStatusCode()) {
            $data = \json_decode($response->getBody()->getContents(), true);

            if (array_key_exists('synonymMappings', $data) && array_key_exists('managedMap', $data['synonymMappings'])) {
                return $data['synonymMappings']['managedMap'];
            }
        }

        return [];
    }

    /**
     * @return bool
     */
    public function addSynonym(Synonym $synonym)
    {
        $data = $this->prepareSynonymData($synonym);

        $request = new Request(
            'PUT',
            sprintf('%sschema/analysis/synonyms/%s', $this->client->getHttpClient()->getConfig('base_uri'), $synonym->getManaged()),
            [],
            \json_encode([(string)$synonym->getIdentifier() => $data])
        );

        $response = $this->client->request($request);
        return $response && 200 === $response->getStatusCode();
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
                throw new \UnexpectedValueException(sprintf('Synonyms array elements must be of type %s. Got %s', Synonym::class, is_object($synonym) ? get_class($synonym) : gettype($synonym)));
            }
            if (!array_key_exists($synonym->getManaged(), $dataPerManaged)) {
                $dataPerManaged[$synonym->getManaged()] = [];
            }
            $dataPerManaged[$synonym->getManaged()][$synonym->getIdentifier()] = $this->prepareSynonymData($synonym);
        }

        $result = array_map(
            function ($managed, $data) {
                $request = new Request(
                    'PUT',
                    sprintf('%sschema/analysis/synonyms/%s', $this->client->getHttpClient()->getConfig('base_uri'), $managed),
                    [],
                    \json_encode($data)
                );

                $response = $this->client->request($request);
                return $response && 200 === $response->getStatusCode();
            },
            array_keys($dataPerManaged),
            $dataPerManaged
        );

        return !in_array(false, $result, true);
    }

    /**
     * @return bool
     */
    public function hasSynonym(Synonym $synonym)
    {
        $request = new Request(
            'GET',
            sprintf('%sschema/analysis/synonyms/%s/%s', $this->client->getHttpClient()->getConfig('base_uri'), $synonym->getManaged(), $synonym->getIdentifier())
        );

        $response = $this->client->request($request);
        return $response && 200 === $response->getStatusCode();
    }

    /**
     * @return bool
     */
    public function removeSynonym(Synonym $synonym)
    {
        $request = new Request(
            'DELETE',
            sprintf('%sschema/analysis/synonyms/%s/%s', $this->client->getHttpClient()->getConfig('base_uri'), $synonym->getManaged(), $synonym->getIdentifier())
        );

        $response = $this->client->request($request);
        return $response && 200 === $response->getStatusCode();
    }

    /**
     * @return array
     */
    protected function prepareSynonymData(Synonym $synonym)
    {
        $data = explode(PHP_EOL, str_replace("\r", '', $synonym->getValue()));

        // Fix to trick Solr into keeping the original word in the tokens
        if (!in_array($synonym->getIdentifier(), $data)) {
            array_unshift($data, $synonym->getIdentifier());
        }

        return $data;
    }
}
