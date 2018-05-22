<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Solr;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Zicht\Bundle\SolrBundle\Exception\BadResponseException;
use Zicht\Bundle\SolrBundle\Exception\RuntimeException;
use Zicht\Http\ClientInterface;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;
use Zicht\Http\RequestFactoryInterface;

/**
 * Class Client
 */
class Client
{
    /** @var ClientInterface|RequestFactoryInterface */
    private $client;
    /** @var RequestFactoryInterface */
    private $requestFactory;
    /** @var Stopwatch */
    private $stopWatch;

    /**
     * Client constructor.
     *
     * @param ClientInterface $client
     * @param Stopwatch|null $stopWatch
     */
    public function __construct(ClientInterface $client, StopWatch $stopWatch = null, RequestFactoryInterface $requestFactory = null)
    {
        $this->client = $client;
        $this->stopWatch = $stopWatch;
        $this->requestFactory = $requestFactory;

        if (!$client instanceof RequestFactoryInterface && null === $requestFactory) {
            throw new RuntimeException('Client should implement a RequestFactoryInterface or a separated RequestFactoryInterface should be provided.');
        }
    }


    /**
     * Selects documents based on the specified query.
     *
     * @param QueryBuilder\Select $query
     * @return mixed
     */
    public function select(QueryBuilder\Select $query)
    {
        return $this->doRequest($query);
    }


    /**
     * Do an update query
     *
     * @param QueryBuilder\Update $update
     * @return mixed
     */
    public function update(QueryBuilder\Update $update)
    {
        return $this->doRequest($update);
    }


    /**
     * Do the request and return the response.
     *
     * Throw an exception wrapping the internal exception if an error occurs
     *
     * @param QueryBuilder\RequestBuilderInterface $handler
     * @return mixed
     */
    protected function doRequest(QueryBuilder\RequestBuilderInterface $handler)
    {
        if (null !== $this->stopWatch) {
            $this->stopWatch->start('solr.request');
        }

        try {

            $request = $handler->createRequest($this->getRequestFactory());
            $response = $this->client->sendRequest($request);

            if (!($status = $response->getStatusCode()) || ($status < 200 || $status >= 300)) {
                throw new BadResponseException($this->getErrMessageFromResponse($response), $response);
            }

            if ($handler instanceof QueryBuilder\ResponseHandlerInterface) {
                return $handler->handle($response);
            }

            return $response;

        } finally {
            if (null !== $this->stopWatch) {
                $this->stopWatch->stop('solr.request');
            }
        }
    }

    /**
     * @return RequestFactoryInterface
     */
    protected function getRequestFactory()
    {
        if (null !== $this->requestFactory) {
            return $this->requestFactory;
        }

        return $this->client;
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    private function getErrMessageFromResponse(ResponseInterface $response)
    {
        if (($body = (string)$response->getBody()) && !empty($body)) {
            if (null !== $respBody = \json_decode($body, true)) {
                if (isset($message['error']['msg'])) {
                    return $respBody['error']['msg'];
                } else {
                    return \json_encode($respBody, JSON_PRETTY_PRINT);
                }
            } else {
                return $body;
            }
        } else {
            return sprintf("Request failed with status code %s (%s)", $response->getStatusCode(), $response->getReasonPhrase());
        }
    }

    /**
     * Do a ping request. Should be configured at 'admin/ping'.
     *
     * @return mixed
     */
    public function ping()
    {
        return $this->doRequest(new QueryBuilder\Ping());
    }

    /**
     * Get all document ids for the specified query.
     *
     * @param string $query
     * @param string $fieldName
     * @return string[]
     */
    public function getDocumentIds($query, $fieldName = 'id')
    {
        $ret = [];
        $select = (new QueryBuilder\Select())
            ->setFieldList($fieldName)
            ->setQuery($query)
        ;
        foreach ($this->select($select)->response->docs as $doc) {
            $ret[]= $doc->$fieldName;
        }
        return $ret;
    }
}
