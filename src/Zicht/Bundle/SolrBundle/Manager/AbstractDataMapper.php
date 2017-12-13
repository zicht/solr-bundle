<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Provides a Template Method object to implement several different parts of the update process.
 *
 * Typically, you would only implement mapDocument() to return the values for the passed entity to index.
 */
abstract class AbstractDataMapper implements DataMapperInterface
{
    protected $classNames = array();

    /**
     * Update the specified entity
     *
     * @param Client $update
     * @param mixed $entity
     * @return void
     */
    public function update(Update $update, $entity)
    {
        $this->addUpdateDocument($update, $entity);
    }


    /**
     * Delete the specified entity from the database
     *
     * @param Update $update
     * @param mixed $entity
     * @return void
     */
    public function delete(Update $update, $entity)
    {
        $update->deleteOne($this->generateObjectIdentity($entity));
    }


    /**
     * Maps the data to an indexable document for Solr
     *
     * @param Update $update
     * @param mixed $entity
     * @return void
     */
    public function addUpdateDocument(Update $update, $entity)
    {
        $params = [];
        if (($boost = $this->getBoost($entity))) {
            $params['boost'] = $boost;
        }
        $doc = ['id' => $this->generateObjectIdentity($entity)];
        $doc += $this->mapDocument($entity);
        $update->add($doc, $params);
    }


    /**
     * Adds a delete instruction
     *
     * @param Update $update
     * @param mixed $entity
     * @return void
     */
    public function addDeleteDocument(Update $update, $entity)
    {
        $update->deleteOne($this->generateObjectIdentity($entity));
    }


    /**
     * Stub that can be overwritten to returns a boost value for the specified entity
     *
     * @param mixed $entity
     * @return float
     */
    protected function getBoost($entity)
    {
        return 0.0;
    }

    /**
     * Return an object id.
     *
     * @param mixed $entity
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    protected function generateObjectIdentity($entity)
    {
        if (method_exists($entity, 'getId')) {
            return sha1(get_class($entity) . ':' . $entity->getId());
        }

        $me = get_class($this);
        $className = get_class($entity);

        throw new \UnexpectedValueException("$className has no getId() method. Either implement it, or override $me::generateObjectIdentity()");
    }

    /**
     * @{inheritDoc}
     */
    public function supports($entity)
    {
        foreach ($this->classNames as $name) {
            if ($entity instanceof $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set a list of classnames that are supported by this mapper.
     *
     * @param array $classNames
     * @return void
     */
    public function setClassNames($classNames)
    {
        $this->classNames = $classNames;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function mapDocument($entity);
}
