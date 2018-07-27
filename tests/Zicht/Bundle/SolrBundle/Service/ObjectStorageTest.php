<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use Zicht\Bundle\SolrBundle\Service\ObjectStorage;

class ObjectStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $storage = new ObjectStorage();
        $storage->add($storage->get(\stdClass::class, 'foo'), 'bar');
        $this->assertSame($storage->get(\stdClass::class, 'foo'), $storage->get(\stdClass::class, 'bar'));
    }

    public function testAdd()
    {
        $storage = new ObjectStorage();
        $instance = new \stdClass();
        $storage->add($instance, 'foo');
        $storage->add($instance, 'bar');
        $this->assertSame($storage->get(\stdClass::class, 'foo'), $storage->get(\stdClass::class, 'bar'));

    }

    public function testRemove()
    {
        $storage = new ObjectStorage();
        $instance = new \stdClass();
        $storage->add($instance, 'foo');
        $storage->add($instance, 'bar');
        $this->assertSame($storage->get(\stdClass::class, 'foo'), $storage->get(\stdClass::class, 'bar'));
        $storage->remove($instance);
        $reflections = new \ReflectionProperty($storage, 'storage');
        $reflections->setAccessible(true);
        $this->assertSame($reflections->getValue($storage)->count(), 0);
        $storage->add($instance, 'foo');
        $storage->add($instance, 'bar');
        $storage->remove($instance, 'foo');
        $this->assertNotSame($storage->get(\stdClass::class, 'foo'), $storage->get(\stdClass::class, 'bar'));
        $storage->remove($storage->get(\stdClass::class, 'foo'));
        $storage->remove($storage->get(\stdClass::class, 'bar'));
        $reflections = new \ReflectionProperty($storage, 'storage');
        $reflections->setAccessible(true);
        $this->assertSame($reflections->getValue($storage)->count(), 0);
        // should not throw errors
        $storage->remove($instance, 'NO_EXISTING');
    }
}