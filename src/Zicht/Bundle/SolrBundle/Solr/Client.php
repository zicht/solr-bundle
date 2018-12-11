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
     * Setup the client
     *
     * @param GuzzleClient $client
     */
    public function __construct(GuzzleClient $client)
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
