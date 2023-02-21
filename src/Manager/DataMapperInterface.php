<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Extract;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * @template T of object
 */
interface DataMapperInterface
{
    /**
     * Request an extract for the specified entity
     *
     * @param T $entity
     */
    public function extract(Extract $extract, $entity);

    /**
     * Request an update for the specified entity
     *
     * @param T $entity
     */
    public function update(Update $update, $entity);

    /**
     * Request an update for the specified entity
     *
     * @param T $entity
     */
    public function delete(Update $update, $entity);

    /**
     * Add an update to the query
     *
     * @param T $entity
     */
    public function addUpdateDocument(Update $update, $entity);

    /**
     * Add a delete to the update query
     *
     * @param T $entity
     */
    public function addDeleteDocument(Update $update, $entity);

    /**
     * Whether or not the mapper supports the passed entity.
     *
     * @param T|object $entity
     * @return bool
     */
    public function supports($entity);

    /**
     * Set a list of classnames that are supported by this mapper.
     *
     * @param array<mixed, class-string<T>> $classNames
     */
    public function setClassNames($classNames);

    /**
     * @return array<mixed, class-string<T>>
     */
    public function getClassNames();
}
