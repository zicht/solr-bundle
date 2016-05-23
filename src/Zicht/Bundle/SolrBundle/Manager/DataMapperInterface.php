<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Class DataMapperInterface
 */
interface DataMapperInterface
{
    public function update(Update $client, $entity, $batch = null);
    public function delete(Update $client, $entity);


    /**
     * Maps the data to an indexable document for Solr
     *
     * @param mixed $data
     * @return mixed
     */
    public function addUpdateDocument(Update $updateQuery, $entity);

    /**
     * @param $entity
     * @return mixed
     */
    public function addDeleteDocument(Update $updateQuery, $entity);


    /**
     * @param mixed $entity
     * @return mixed
     */
    public function supports($entity);
}