<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Zicht\Bundle\SolrBundle\Solr\Client;

/**
 * A controller that shows a status; useful for monitoring purposes.
 */
class StatusController extends AbstractController
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Reports a 503 if SOLR's ping query does not succeed.
     *
     * @return Response
     * @Route("solr")
     */
    public function statusAction()
    {
        $response = new Response('', 200, ['Content-Type' => 'text/plain']);

        try {
            $this->client->ping();

            $response->setContent('ping succeeded');
        } catch (\Exception $e) {
            $response
                ->setStatusCode(503)
                ->setContent((string)$e->getMessage())
            ;
        }

        return $response;
    }
}