<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Solr;

final class ObjectStorage
{
    /** @var \SplObjectStorage  */
    private $storage;

    /**
     * ObjectStorage constructor.
     */
    public function __construct()
    {
        $this->storage = new \SplObjectStorage();
    }

    /**
     * @param string $className
     * @param string $scope
     * @return object
     */
    public function get($className, $scope)
    {
        foreach ($this->storage as $object) {
            list($class, $scopes) = $this->storage[$object];
            if ($class === $className && in_array($scope, $scopes)) {
                return $object;
            }
        }

        $instance = new $className();
        $this->add($instance, $scope);

        return $instance;
    }

    /**
     * @param object $object
     * @param string $scope
     */
    public function add($object, $scope)
    {
        if ($this->storage->contains($object)) {
            list($className, $scopes) = $this->storage[$object];

            if (!in_array($scope, $scopes)) {
                $scopes[] = $scope;
            }

            $this->storage[$object] = [$className, $scopes];
        } else {
            $this->storage->attach($object, [get_class($object), (array)$scope]);
        }
    }

    /**
     * @param object $object
     * @param string|null $scope
     */
    public function remove($object, $scope = null)
    {
        if (!$this->storage->contains($object)) {
            return;
        }

        if (null === $scope) {
            $this->storage->detach($object);
        } else {
            list($className, $scopes) = $this->storage[$object];

            if (false !== $index = array_search($scope, $scopes)) {
                unset($scopes[$index]);
            }

            $this->storage[$object] = [$className, $scopes];
        }
    }
}
