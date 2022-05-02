<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Extract;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces\Extractable;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

/**
 * Provides a Template Method object to implement several different parts of the update process.
 * Typically, you would only implement mapDocument() to return the values for the passed entity to index.
 *
 * @template T of object
 * @implements DataMapperInterface<T>
 */
abstract class AbstractDataMapper implements DataMapperInterface
{
    /** @var array<mixed, class-string<T>> */
    protected $classNames = [];

    /** {@inheritDoc} */
    public function extract(Extract $extract, $entity)
    {
        if (!($entity instanceof Extractable)) {
            throw new \BadFunctionCallException('Extract is called for a wrong entity type. Check your configuration');
        }

        $params = [];
        if (($boost = $this->getBoost($entity))) {
            $params['boost'] = $boost;
        }
        $id = $this->generateObjectIdentity($entity);
        $doc = $this->mapDocument($entity);
        $extract->extract($id, $doc, $params, $entity->getFileResource());
    }

    /** {@inheritDoc} */
    public function update(Update $update, $entity)
    {
        $this->addUpdateDocument($update, $entity);
    }

    /** {@inheritDoc} */
    public function delete(Update $update, $entity)
    {
        $update->deleteOne($this->generateObjectIdentity($entity));
    }

    /** {@inheritDoc} */
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

    /** {@inheritDoc} */
    public function addDeleteDocument(Update $update, $entity)
    {
        $update->deleteOne($this->generateObjectIdentity($entity));
    }

    /**
     * Stub that can be overwritten to returns a boost value for the specified entity
     *
     * @param T $entity
     * @return float
     */
    protected function getBoost($entity)
    {
        return 0.0;
    }

    /**
     * Return an object id.
     *
     * @param T $entity
     * @return string
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

    /** {@inheritDoc} */
    public function supports($entity)
    {
        foreach ($this->classNames as $name) {
            if ($entity instanceof $name) {
                return true;
            }
        }
        return false;
    }

    /** {@inheritDoc} */
    public function setClassNames($classNames)
    {
        $this->classNames = $classNames;
    }

    /** {@inheritDoc} */
    public function getClassNames()
    {
        return $this->classNames;
    }

    /**
     * Map document data
     *
     * @param T $entity
     * @return array
     */
    abstract protected function mapDocument($entity);
}
