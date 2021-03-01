<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager\Doctrine;

use Zicht\Bundle\SolrBundle\Manager\AbstractDataMapper;

/**
 * Base class used for doctrine entity mapping
 *
 * @template T of object
 * @extends AbstractDataMapper<T>
 */
abstract class EntityMapper extends AbstractDataMapper
{
}
