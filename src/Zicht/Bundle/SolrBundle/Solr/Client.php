<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\ResponseInterface;
use Zicht\Bundle\SolrBundle\Exception\NotFoundException;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

/**
 * Class Client
 */
class Client
{
    private $lastRequest = null;
    private $lastResponse = null;

    /**
     * @var string
     */
    private $absoluteBaseUrl;

    /**
     * @var string
     */
    private $core;

    /**
     * @var array
     */
    public $logs = [];

    /**
     * Setup the client
     *
     * @param GuzzleClient $client
     */
    public function __construct(GuzzleClient $client, $absoluteBaseUrl, $core)
    {
        $this->http = $client;
        $this->absoluteBaseUrl = $absoluteBaseUrl;
        $this->core = $core;
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
     * Do an extract query
     *
     * @param QueryBuilder\Extract $extract
     * @return mixed
     */
    public function extract(QueryBuilder\Extract $extract)
    {
        return $this->doRequest($extract);
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
        /** @var Request $request */
        $request = $handler->createRequest($this->http);

        $this->lastRequest = $request;
        try {
            $response = $this->http->send($request);
            $this->lastResponse = $response;

            if ($handler instanceof QueryBuilder\ResponseHandlerInterface) {
                $response = $handler->handle($response);
            }

            $this->logs[] = ['response' => $response, 'requestUri' => $request->getUrl()];
        } catch (BadResponseException $e) {
            $this->lastResponse = $e->getResponse();
            if ($e->getRequest()->getBody()) {
                $e->getRequest()->getBody()->seek(0);
            }
            if (defined('STDERR')) {
                // fwrite(STDERR, $e->getRequest()->getBody()->getContents());
                // fwrite(STDERR, "\n\n");
                fwrite(STDERR, $e->getResponse()->getBody()->getContents());
            }
            throw new Exception($e->getMessage(), null, $e);
        }

        return $response;
    }

    /**
     * @return GuzzleClient
     */
    public function getHttpClient()
    {
        return $this->http;
    }

    /**
     * Allows to pass guzzle requests straight to SOLR.
     *
     * @param Request $request
     * @return \GuzzleHttp\Message\FutureResponse|ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     */
    public function request(Request $request)
    {
        try {
            $this->lastRequest = $request;
            $this->lastResponse = $response = $this->http->send($request);
            $this->logs[] = ['response' => $response, 'requestUri' => $request->getUrl()];

            return $response;
        } catch (BadResponseException $e) {
            $this->lastResponse = $e->getResponse();

            if ($this->lastResponse->getStatusCode() === 404) {
                $data = $e->getResponse()->json();

                throw new NotFoundException(sprintf('SOLR Error: %s', $data['error']['msg']));
            }

            if ($e->getRequest()->getBody()) {
                $e->getRequest()->getBody()->seek(0);
            }

            throw new Exception($e->getMessage(), null, $e);
        }
    }
    
    public function reload()
    {
        $request = new Request('GET', $this->absoluteBaseUrl . 'admin/cores?action=RELOAD&core=' . $this->core);
        return $this->request($request);
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
            $ret[] = $doc->{$fieldName};
        }
        return $ret;
    }

    /**
     * Returns the last request issued to SOLR. This is typically for debugging purposes.
     *
     * @return mixed
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Returns the last response issued by SOLR. This is typically for debugging purposes.
     *
     * @return ResponseInterface
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
