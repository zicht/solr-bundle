<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class HttpResponseException
 * @package Zicht\Bundle\SolrBundle\Exception
 */
class HttpResponseException extends \RuntimeException implements SolrExceptionInterface
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
