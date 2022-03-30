<?php
/**
 * @copyright Zicht online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * A controller that shows a status; useful for monitoring purposes.
 */
class StatusController extends Controller
{
    /**
     * Reports a 503 if SOLR's ping query does not succeed.
     *
     * @return Response
     *
     * @Route("solr")
     */
    public function statusAction()
    {
        $response = new Response('', 200, array('Content-Type' => 'text/plain'));

        $client = $this->get('zicht_solr.solr');
        try {
            $client->ping();

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