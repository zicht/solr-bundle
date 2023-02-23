<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update as UpdateQuery;

/**
 * As opposed to the regular SolrManager this SolrEntityManager keeps track of the handled objects to not handle the same
 * object more than once per request. Also the SolrEntityManager checks if an entity has indexable related entities and
 * runs an update on those too.
 *
 * @example To use the SolrEntityManager instead of the default SolrManager, add to your app/config/bundles/zicht_solr.yml:
 * zicht_solr:
 *     manager:
 *         type: 'entity'
 */
class SolrEntityManager extends SolrManager
{
    /** @var string[] */
    private $updatedEntityHashes = [];

    /** @var string[] */
    private $deletedEntityHashes = [];

    /**
     * @param mixed $entity
     * @return bool
     * @psalm-assert object $entity
     */
    protected static function validateEntity($entity)
    {
        if (!is_object($entity)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            throw new \InvalidArgumentException(sprintf('Received %s while expecting an object. %s can only handle objects.', gettype($entity), isset($backtrace[1]) ? $backtrace[1]['class'] . '::' . $backtrace[1]['function'] . '()' : get_class()));
        }

        return true;
    }

    public function updateBatch($records, $incrementCallback = null, $errorCallback = null, $deleteFirst = false)
    {
        $this->update = new UpdateQuery();

        $totalCount = $updatedCount = 0;
        /**
         * Recursive Closure to be able to travel deep into entity relations but
         * still keep updates in one single transaction ($update object)
         */
        $innnerUpdateBatch = function (array $entities) use (&$innnerUpdateBatch, &$incrementCallback, &$errorCallback, $deleteFirst, &$totalCount, &$updatedCount) {
            foreach ($entities as $entity) {
                self::validateEntity($entity);

                if (in_array(spl_object_hash($entity), $this->updatedEntityHashes)) {
                    ++$totalCount;
                    continue;
                }

                if ($mapper = $this->getMapper($entity)) {
                    ++$updatedCount;
                    try {
                        if ($deleteFirst) {
                            $mapper->delete($this->update, $entity);
                            $this->deletedEntityHashes[] = spl_object_hash($entity);
                        }
                        $mapper->update($this->update, $entity);
                        $this->updatedEntityHashes[] = spl_object_hash($entity);
                    } catch (\Exception $e) {
                        if ($errorCallback) {
                            call_user_func($errorCallback, $entity, $e);
                        }
                    }
                    if ($incrementCallback) {
                        call_user_func($incrementCallback, $totalCount);
                    }
                }

                if ($entity instanceof IndexableRelationsInterface) {
                    $innnerUpdateBatch($entity->getIndexableRelations());
                }

                ++$totalCount;
            }
        };
        $innnerUpdateBatch($records);

        if ($incrementCallback) {
            call_user_func($incrementCallback, $totalCount);
        }

        $this->update->commit();
        $this->client->update($this->update);
        $this->update = null;

        return [$totalCount, $updatedCount];
    }

    public function update($entity)
    {
        self::validateEntity($entity);

        if (!$this->enabled) {
            return false;
        }

        if (in_array(spl_object_hash($entity), $this->updatedEntityHashes)) {
            return false;
        }

        $updated = parent::update($entity);
        if ($updated) {
            $this->updatedEntityHashes[] = spl_object_hash($entity);
        }

        // Even if updating failed (e.g. no mapper found) still update the relations
        if ($entity instanceof IndexableRelationsInterface) {
            foreach ($entity->getIndexableRelations() as $relation) {
                $this->update($relation);
            }
        }

        return $updated;
    }

    public function delete($entity)
    {
        self::validateEntity($entity);

        if (!$this->enabled) {
            return false;
        }

        if (in_array(spl_object_hash($entity), $this->deletedEntityHashes)) {
            return false;
        }

        $deleted = parent::delete($entity);
        if ($deleted) {
            $this->deletedEntityHashes[] = spl_object_hash($entity);
        }

        $deleteRelations = [];
        if ($entity instanceof DeleteIndexableRelationsInterface) {
            foreach ($entity->getDeleteIndexableRelations() as $deleteRelation) {
                $deleteRelations[] = $deleteRelation;
                $this->delete($deleteRelation);
            }
        }

        // Even if deletion failed (e.g. no mapper found) still update the relations
        if ($entity instanceof IndexableRelationsInterface) {
            foreach ($entity->getIndexableRelations() as $relation) {
                if (in_array(spl_object_hash($entity), $this->deletedEntityHashes) || \in_array($relation, $deleteRelations, true)) {
                    continue;
                }
                $this->update($relation);
            }
        }

        return $deleted;
    }
}
