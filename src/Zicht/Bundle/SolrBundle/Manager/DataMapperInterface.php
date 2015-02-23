<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use \Solarium\Client;

/**
 * Class DataMapperInterface
 */
interface DataMapperInterface
{
    public function update(Client $manager, $entity);
    public function delete(Client $manager, $entity);


    /**
     * Maps the data to an indexable document for Solr
     *
     * @param mixed $data
     * @return mixed
     */
    public function addUpdateDocument($updateQuery, $entity);

    /**
     * @param $entity
     * @return mixed
     */
    public function addDeleteDocument($updateQuery, $entity);


    /**
     * @param mixed $entity
     * @return mixed
     */
    public function supports($entity);
}