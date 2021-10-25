<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Doctrine\Common\Util\ClassUtils;
use Zicht\Bundle\SolrBundle\Manager\AbstractDataMapper;

/**
 * Base class used for doctrine entity mapping
 *
 * @template T of object
 * @extends AbstractDataMapper<T>
 */
abstract class EntityMapper extends AbstractDataMapper
{
    /**
     * Doctrine might create a proxy class for an entity. We need the real classname, otherwise the Solr ID might differ
     * from mapping the entity without proxy class and the same data will be in the Solr index twice.
     * ClassUtils::getRealClass() will strip off the proxy namespace.
     * {@inheritDoc}
     */
    protected function generateObjectIdentity($entity)
    {
        if (method_exists($entity, 'getId')) {
            return sha1(ClassUtils::getClass($entity) . ':' . $entity->getId());
        }

        return parent::generateObjectIdentity($entity);
    }
}
