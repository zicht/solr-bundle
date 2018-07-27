<?php
namespace Zicht\Bundle\SolrBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class HttpResponseException extends \HttpResponseException implements SolrExceptionInterface
{
    /** @var Response */
    private $response;

    /**
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
