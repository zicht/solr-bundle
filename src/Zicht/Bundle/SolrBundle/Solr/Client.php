<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

/**
 * Class Client
 */
class Client
{
    private $lastRequest = null;
    private $lastResponse = null;

    /**
     * @var array
     */
    public $logs = [];

    /**
     * @var ClientInterface
     */
    protected $http;

    /**
     * Setup the client
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->http = $client;
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
     * @see https://lucene.apache.org/solr/5_0_0/solr-solrj/org/apache/solr/common/SolrException.html
     * @see https://lucene.apache.org/solr/4_7_0/solr-solrj/org/apache/solr/common/SolrException.html
     *
     * @throws Exception
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
            $ret[] = $doc->$fieldName;
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
