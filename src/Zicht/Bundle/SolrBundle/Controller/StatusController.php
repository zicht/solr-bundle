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
        try {
            $this->get('zicht_solr.solr')->ping();
            return new Response('ping succeeded', Response::HTTP_OK, $this->getTexTypetHeaders());
        } catch (\Exception $e) {
            return new Response((string)$e->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE, $this->getTexTypetHeaders());
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