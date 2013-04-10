<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Builder\Solarium;

interface SolariumInterface {
    /**
     * @abstract
     *
     * @param \Zicht\Bundle\SolrBundle\Builder\Solarium\Solarium_Client|\Zicht\Bundle\SolrBundle\Builder\Solarium\Solarium_Query_Update $client
     *
     * @return Solarium_Document_ReadWrite
     */
    public function createDocument($client);


    public function generateObjectIdentity($entity);
}