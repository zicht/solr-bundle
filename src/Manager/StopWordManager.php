<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use GuzzleHttp\Psr7\Request;
use Zicht\Bundle\SolrBundle\Entity\StopWord;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * Manages the stop word's registered in SOLR.
 */
class StopWordManager
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
     * Return all the stopwords managed in SOLR.
     *
     * @param string $managed
     * @return array
     */
    public function findAll($managed)
    {
        $request = new Request('GET', sprintf('%sschema/analysis/stopwords/%s', $this->client->getHttpClient()->getConfig('base_uri'), $managed));
        $response = $this->client->request($request);

        if ($response && 200 === $response->getStatusCode()) {
            $data = \json_decode($response->getBody()->getContents(), true);

            if (array_key_exists('wordSet', $data) && array_key_exists('managedList', $data['wordSet'])) {
                return $data['wordSet']['managedList'];
            }
        }

        return [];
    }

    /**
     * @return bool
     */
    public function addStopWord(StopWord $stopWord)
    {
        $request = new Request(
            'PUT',
            sprintf('%sschema/analysis/stopwords/%s', $this->client->getHttpClient()->getConfig('base_uri'), $stopWord->getManaged()),
            [],
            \json_encode([$stopWord->getValue()])
        );

        $response = $this->client->request($request);
        return $response && 200 === $response->getStatusCode();
    }

    /**
     * Adds multiple stopword at once
     *
     * @param StopWord[] $stopWords
     * @return bool
     */
    public function addStopWords($stopWords)
    {
        $dataPerManaged = [];
        foreach ($stopWords as $stopWord) {
            if (!$stopWord instanceof StopWord) {
                throw new \UnexpectedValueException(sprintf('Stop words array elements must be of type %s. Got %s', StopWord::class, is_object($stopWord) ? get_class($stopWord) : gettype($stopWord)));
            }
            if (!array_key_exists($stopWord->getManaged(), $dataPerManaged)) {
                $dataPerManaged[$stopWord->getManaged()] = [];
            }
            $dataPerManaged[$stopWord->getManaged()][] = $stopWord->getValue();
        }

        $result = array_map(
            function ($managed, $data) {
                $request = new Request(
                    'PUT',
                    sprintf('%sschema/analysis/stopwords/%s', $this->client->getHttpClient()->getConfig('base_uri'), $managed),
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
    public function hasStopWord(StopWord $stopWord)
    {
        $request = new Request(
            'GET',
            sprintf('%sschema/analysis/stopwords/%s/%s', $this->client->getHttpClient()->getConfig('base_uri'), $stopWord->getManaged(), $stopWord->getValue())
        );

        $response = $this->client->request($request);
        return $response && 200 === $response->getStatusCode();
    }

    /**
     * @return bool
     */
    public function removeStopWord(StopWord $stopWord)
    {
        $request = new Request(
            'DELETE',
            sprintf('%sschema/analysis/stopwords/%s/%s', $this->client->getHttpClient()->getConfig('base_uri'), $stopWord->getManaged(), $stopWord->getValue())
        );

        $response = $this->client->request($request);
        return $response && 200 === $response->getStatusCode();
    }
}
