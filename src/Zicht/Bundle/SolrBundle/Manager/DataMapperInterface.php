<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Class DataMapperInterface
 */
interface DataMapperInterface
{
    /**
     * Request an update for the specified entity
     *
     * @param Update $update
     * @param mixed $entity
     * @return mixed
     */
    public function update(Update $update, $entity);

    /**
     * Request an update for the specified entity
     *
     * @param Update $update
     * @param mixed $entity
     * @return mixed
     */
    public function delete(Update $update, $entity);

    /**
     * Add an update to the query
     *
     * @param Update $update
     * @param mixed $entity
     * @return mixed
     */
    public function addUpdateDocument(Update $update, $entity);

    /**
     * Add a delete to the update query
     *
     * @param Update $update
     * @param mixed $entity
     * @return mixed
     */
    public function addDeleteDocument(Update $update, $entity);

    /**
     * Whether or not the mapper supports the passed entity.
     *
     * @param mixed $entity
     * @return mixed
     */
    public function supports($entity);
}