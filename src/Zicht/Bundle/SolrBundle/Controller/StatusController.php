<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

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
        $resp = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR, $this->getTexTypetHeaders());
        $client = $this->get('zicht_solr.solr');

        try {
            $client->ping();
            $resp
                ->setStatusCode($resp::HTTP_OK)
                ->setContent('ping succeeded');
        } catch (\Exception $e) {
            $resp
                ->setStatusCode($resp::HTTP_SERVICE_UNAVAILABLE)
                ->setContent((string)$e->getMessage());
        } finally {
            return $resp;
        }
    }

    /**
     * @return array
     */
    private function getTexTypetHeaders()
    {
        return [
            'Content-Type' => 'text/plain;charset=utf-8',
            'X-Content-Type-Options' => 'nosniff'
        ];
    }
}