<?php
namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use Zicht\Bundle\SolrBundle\Mapping\MethodMapper;
use Zicht\Bundle\SolrBundle\Mapping\MethodMergeMapper;
use Zicht\Bundle\SolrBundle\Mapping\PropertyMethodMapper;
use Zicht\Bundle\SolrBundle\Mapping\PropertyValueMapper;
use Zicht\Bundle\SolrBundle\Mapping\StaticMethodMapper;
use Zicht\Bundle\SolrBundle\Mapping\StaticValueMapper;
use Zicht\Bundle\SolrBundle\Service\ObjectStorage;
use Zicht\Bundle\SolrBundle\Service\ObjectStorageScopes;

class PropertyClass
{
    private $a;

    public static function newInstance($a)
    {
        $i = new self();
        $i->a = $a;
        return $i;
    }
}

class ReverseMethod
{
    private $i = 1;

    public function fromClass($a)
    {
        return $this->doReverse($a->a);
    }

    public function fromValue($a)
    {
        return $this->doReverse($a);
    }

    private function doReverse($a)
    {
        for ($i = 0; $i < $this->i; $i++) {
            $a = strrev($a);
        }
        $this->i++;
        return $a;
    }
}

class MapperTest extends \PHPUnit_Framework_TestCase
{
    private function newInvokeMok($return)
    {
        $stub = $this
            ->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $stub
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($return));

        return $stub;
    }

    public function methodMergeMapperProvider()
    {
        return [
            [['a' => 'a'], ['a' => 'b']],
            [['c' => 'c'], ['c' => ['d']]],
            [['d' => ['a' => 'a']], ['d' => ['a' => 'b']]],
            [['e' => ['a' => ['b' => 'b']]], ['e' => ['a' => ['b' => 'c']]]],
            [['e' => ['a' => ['b' => 'b']]], ['e' => ['a' => ['b' => 'c']]]],
            [
                ['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => ['a' => 'a'], 'e' => ['a' => ['b' => 'b']]],
                ['a' => 'b', 'b' => 'c', 'c' => ['d'], 'd' => ['a' => 'b'], 'e' => ['a' => ['b' => 'c']]],
            ]
        ];
    }

    /**
     * @dataProvider methodMergeMapperProvider
     */
    public function testMethodMergeMapper($a, $b)
    {
        $stub = $this->newInvokeMok($b);
        $mapper = new MethodMergeMapper(\stdClass::class, '__invoke');
        $mapper->append($stub, $a);
        $this->assertSame($b, $a);
    }

    public function testMethodMapper()
    {
        $stub = $this->newInvokeMok('foo');
        $data = [];
        $mapper = new MethodMapper('bar', \stdClass::class, '__invoke');
        $mapper->append($stub, $data);
        $this->assertSame(['bar' => 'foo'], $data);
    }

    public function testPropertyValueMapper()
    {
        $class = PropertyClass::newInstance('bar');
        $data = [];
        $mapper = new PropertyValueMapper('foo', PropertyClass::class, 'a');
        $mapper->append($class, $data);
        $this->assertSame(['foo' => 'bar'], $data);
    }

    public function testPropertyMethodMapper()
    {
        $object = PropertyClass::newInstance('foo');
        $data = [];
        $mapper = new PropertyMethodMapper('foo', PropertyClass::class, 'a', ReverseMethod::class, 'fromValue');
        $mapper->append($object, $data);
        $this->assertSame(['foo' => 'oof'], $data);
        $mapper->append($object, $data);
        $this->assertSame(['foo' => 'oof'], $data);
        $os = new ObjectStorage();
        $os->add(new ReverseMethod(), ObjectStorageScopes::SCOPE_MAPPING_MARSHALLER);
        $mapper->append($object, $data, $os);
        $this->assertSame(['foo' => 'oof'], $data);
        $mapper->append($object, $data, $os);
        $this->assertSame(['foo' => 'foo'], $data);
        $mapper->append($object, $data, $os);
        $this->assertSame(['foo' => 'oof'], $data);
    }

    public function testStaticValueMapper()
    {
        $data = [];
        $mapper = new StaticValueMapper('foo', 'bar');
        $mapper->append(null, $data);
        $this->assertSame(['foo' => 'bar'], $data);
    }

    public function testStaticMethodMapper()
    {
        $object = new \stdClass();
        $object->a = 'foo';
        $data = [];
        $mapper = new StaticMethodMapper('foo', ReverseMethod::class, 'fromClass');
        $mapper->append($object, $data);
        $this->assertSame(['foo' => 'oof'], $data);
        $mapper->append($object, $data);
        $this->assertSame(['foo' => 'oof'], $data);
        $os = new ObjectStorage();
        $os->add(new ReverseMethod(), ObjectStorageScopes::SCOPE_MAPPING_MARSHALLER);
        $mapper->append($object, $data, $os);
        $this->assertSame(['foo' => 'oof'], $data);
        $mapper->append($object, $data, $os);
        $this->assertSame(['foo' => 'foo'], $data);
        $mapper->append($object, $data, $os);
        $this->assertSame(['foo' => 'oof'], $data);
    }
}
