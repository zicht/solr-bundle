<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Entity\StopWord;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * Manages the stop word's registered in SOLR.
 */
class StopWordManager
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
     * Return all the stopwords managed in SOLR.
     *
     * @param $managed
     * @return array
     */
    public function findAll($managed)
    {
        $request = $this->client->getHttpClient()->createRequest('GET', sprintf('schema/analysis/stopwords/%s', $managed));
        $response = $this->client->request($request);

        if (200 === $response->getStatusCode()) {
             $data = $response->json();

             if (array_key_exists('wordSet', $data) && array_key_exists('managedList', $data['wordSet'])) {
                 return $data['wordSet']['managedList'];
             }
        }

        return [];
    }

    /**
     * Adds an stopword
     *
     * @param StopWord $stopWord
     * @return bool
     */
    public function addStopWord(StopWord $stopWord)
    {
        $request =  $this->client->getHttpClient()->createRequest(
            'PUT',
            sprintf('schema/analysis/stopwords/%s', $stopWord->getManaged()),
            [
                'body' => \json_encode([$stopWord->getValue()])
            ]
        );

        return 200 === $this->client->request($request)->getStatusCode();
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
                throw new \UnexpectedValueException(
                    sprintf(
                        'Stop words array elements must be of type %s. Got %s',
                        StopWord::class,
                        is_object($stopWord) ? get_class($stopWord) : gettype($stopWord)
                    )
                );
            }
            if (!array_key_exists($stopWord->getManaged(), $dataPerManaged)) {
                $dataPerManaged[$stopWord->getManaged()] = [];
            }
            $dataPerManaged[$stopWord->getManaged()][] = $stopWord->getValue();
        }

        $result = array_map(
            function ($managed, $data) {
                $request =  $this->client->getHttpClient()->createRequest(
                    'PUT',
                    sprintf('schema/analysis/stopwords/%s', $managed),
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
     * Has an stopword.
     *
     * @param StopWord $stopWord
     * @return bool
     */
    public function hasStopWord(StopWord $stopWord)
    {
        $request =  $this->client->getHttpClient()->createRequest(
            'GET',
            sprintf('schema/analysis/stopwords/%s/%s', $stopWord->getManaged(), $stopWord->getValue())
        );

        return 200 === $this->client->request($request)->getStatusCode();
    }

    /**
     * Removes an stopword.
     *
     * @param StopWord $stopWord
     * @return bool
     */
    public function removeStopWord(StopWord $stopWord)
    {
        $request =  $this->client->getHttpClient()->createRequest(
            'DELETE',
            sprintf('schema/analysis/stopwords/%s/%s', $stopWord->getManaged(), $stopWord->getValue())
        );

        return 200 === $this->client->request($request)->getStatusCode();
    }
}
