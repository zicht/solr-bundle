<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Controller;

use \Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use \Solarium\Core\Client\Client;
use \Symfony\Bundle\FrameworkBundle\Controller\Controller;
use \Symfony\Component\HttpFoundation\Response;

/**
 * A controller that shows a status; useful for monitoring purposes. Routing should be mounted on some arbitratry base
 * url, such as _status, like this:
 *
 * (routing.yml)
 *
 *
 *
 * Class StatusController
 * @package Zicht\Bundle\SolrBundle\Controller
 */
class StatusController extends Controller
{
    /**
     * @Route("solr")
     */
    public function statusAction()
    {
        $response = new Response('', 200, array('Content-Type' => 'text/plain'));

        /** @var $client Client */
        $client = $this->get('solarium.client');
        try {
            $client->ping($client->createPing());

            $response->setContent('ping succeeded');
        } catch (\Exception $e) {
            $response
                ->setStatusCode(503)
                ->setContent((string)$e->getMessage())
            ;
        }

        return $response;
    }
};