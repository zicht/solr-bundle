<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Exception;

use Psr\Http\Message\ResponseInterface;

class BadResponseException extends \RuntimeException implements SolrExceptionInterface
{
    /** @var ResponseInterface  */
    private $response;

    /**
     * BadResponseException constructor.
     *
     * @param string $message
     * @param ResponseInterface $response
     */
    public function __construct($message, ResponseInterface $response)
    {
        parent::__construct($message);
        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}