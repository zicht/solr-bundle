<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Zicht\Bundle\SolrBundle\Exception\ConfigurationException;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

class Client
{
    private $lastRequest = null;
    private $lastResponse = null;

    /**
     * @var ClientInterface
     */
    private $http;

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

    public function __construct(array $options, string $clientClass)
    {
        [$this->absoluteBaseUrl, $this->core] = $this->parseSolrUrlFromOptions($options);
        $this->http = new $clientClass(['base_uri' => sprintf('%s%s/', $this->absoluteBaseUrl, $this->core)]);
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
     * Throw an exception wrapping the internal exception if an error occurs.
     *
     * @param QueryBuilder\RequestBuilderInterface $handler
     * @return mixed
     * @throws Exception
     * @see https://solr.apache.org/docs/5_5_0/solr-solrj/org/apache/solr/common/SolrException.html
     * @see https://solr.apache.org/docs/8_4_0/solr-solrj/org/apache/solr/common/SolrException.html
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

            $this->logs[] = ['response' => $response, 'requestUri' => $request->getUri()];
        } catch (BadResponseException $be) {
            $this->lastResponse = $be->getResponse();
            if ($be->getRequest()->getBody()) {
                $be->getRequest()->getBody()->seek(0);
            }
            $content = $be->getResponse()->getBody()->getContents();
            $contentType = $this->lastResponse->getHeaderLine('Content-Type');
            $errorMsg = $be->getMessage();
            if (preg_match('!^application/json!', $contentType) || preg_match('!^text/plain!', $contentType)) {
                try {
                    // possibly content is a json-string containing a SolrException.
                    $solrException = \GuzzleHttp\json_decode($content);
                    if (property_exists($solrException, 'error') && property_exists($solrException->error, 'msg')) {
                        $errorMsg = $solrException->error->msg;
                    }
                } catch (\InvalidArgumentException $invalidArgumentException) {
                    // we keep the original errorMsg. It still contains all the info we need.
                }
            }
            if (defined('STDERR')) {
                fwrite(STDERR, PHP_EOL . $errorMsg . PHP_EOL);
                fwrite(STDERR, PHP_EOL . $content . PHP_EOL);
            }
            throw new Exception($errorMsg, null, $be);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), null, $e);
        }
        return $response;
    }

    /**
     * @return ClientInterface
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
            $this->logs[] = ['response' => $response, 'requestUri' => $request->getUri()];

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
            ->setQuery($query);
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

    /**
     * @param array $options
     * @return string[]
     */
    private function parseSolrUrlFromOptions(array $options): array
    {
        if (!array_key_exists('url', $options)) {
            throw new ConfigurationException('Solr url is missing from configuration options');
        }
        if ($options['url'] === null || (string)$options['url'] === '') {
            throw new ConfigurationException('Solr url is empty in configuration options');
        }
        if (!is_string($options['url'])) {
            throw new ConfigurationException(sprintf('Solr url should be a string in configuration options, %s given', gettype($options['url'])));
        }
        if (strpos($options['url'], '://') === false) {
            $options['url'] = 'http://' . $options['url'];
        }

        $url = parse_url($options['url']);
        if ($url === false) {
            throw new ConfigurationException('Could not parse configured Solr url');
        }

        if (!isset($url['host']) || $url['host'] === '') {
            throw new ConfigurationException('Could not parse host from configured Solr url. Please configure a full URL');
        }
        if (!isset($url['scheme']) || $url['scheme'] !== 'http') {
            throw new ConfigurationException('No schema or an unsupported schema was configured for Solr url. Only http is supported');
        }

        $core = null;
        if (isset($url['query'])) {
            parse_str($url['query'], $query);
            if (isset($query['core'])) {
                $core = $query['core'];
            }
        }
        if ((string)$core === '') {
            throw new ConfigurationException('Solr core was not configured through Solr url in configuration options');
        }

        $baseUrl = sprintf('%s://%s%s/%s/', $url['scheme'], $url['host'], (isset($url['port']) ? ':' . $url['port'] : ''), ($url['path'] ? trim($url['path'], '/') : ''));

        return [$baseUrl, $core];
    }
}
