<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\SimpleCache;

use Psr\SimpleCache\CacheInterface;

class SimpleCacheTest extends \PHPUnit_Framework_TestCase
{

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        $base = sys_get_temp_dir() . '/phpunit__simple_cache';
        
        foreach(glob($base . '/*', GLOB_ONLYDIR) as $dir) {
            rmdir($dir);
        }

        rmdir($base);
    }

    /**
     * @return array
     */
    public function CacheProvider()
    {
        return [
            [new ArrayCache()],
            [new FilesystemCache(sys_get_temp_dir() . '/phpunit__simple_cache/' .$this->getString(5))],
        ];
    }

    /**
     * @dataProvider CacheProvider
     */
    public function  testValidateDataTypes(CacheInterface $cache = null)
    {
        $types = [
            'string' => $this->getString(),
            'integer' => rand(100,999),
            'float' => mt_rand()/mt_getrandmax(),
            'array' => array_fill(0, rand(3,9), $this->getString(5)),
        ];


        $this->assertTrue($cache->setMultiple($types));

        foreach ($cache->getMultiple(array_keys($types)) as $key => $value) {
            $this->assertSame($value, $types[$key]);
        }

        $this->assertTrue($cache->deleteMultiple(array_keys($types)));

        foreach (array_keys($types) as $name) {
            $this->assertFalse($cache->has($name));
            $this->assertNull($cache->get($name));
        }

        $this->assertFalse($cache->delete('foo'));

        // object should be checked lossless because of
        // the of the object serialisation
        $object = new \stdClass();
        $object->foo = 'bar';

        $this->assertTrue($cache->set('object', $object));
        $this->assertEquals($cache->get('object'), $object);
        $this->assertTrue($cache->delete('object'));
        $this->assertNull($cache->get('object'));

        foreach ($types as $name => $value) {
            $this->assertTrue($cache->set($name, $value));
        }

        $this->assertTrue($cache->clear());

        foreach (array_keys($types) as $name) {
            $this->assertFalse($cache->has($name));
            $this->assertNull($cache->get($name));
        }
    }

    /**
     * @dataProvider MultiInvalidArgumentExceptionProvider
     * @expectedException \Zicht\SimpleCache\InvalidArgumentException
     */
    public function testException(CacheInterface $cache, $method, ...$args)
    {
        $cache->{$method}(...$args);
    }

    /**
     * @dataProvider CacheInvalidKeysProvider
     * @expectedException \Zicht\SimpleCache\InvalidKeyException
     */
    public function testInvalidKeyException(CacheInterface $cache, $key)
    {
        $cache->set($key, null);
    }

    /**
     * @expectedException \Zicht\SimpleCache\RuntimeException
     */
    public function testInvalidBaseFileCahe()
    {
        (new FilesystemCache(null));
    }

    public function testBaseFileCache()
    {
        $cache = new FilesystemCache('/tmp/');
        $reflect = new \ReflectionProperty($cache, 'base');
        $reflect->setAccessible(true);
        $this->assertSame('/tmp', $reflect->getValue($cache));
    }

    public function testCorruptFile()
    {
        $cache = new FilesystemCache('/tmp/');
        $reflect = new \ReflectionMethod($cache, 'getFile');
        $reflect->setAccessible(true);
        $file = $reflect->invoke($cache, 'foo');
        touch($file);
        $this->assertSame('bar', $cache->get('foo', 'bar'));
        unlink($file);
    }


    /**
     * @return array
     */
    public function MultiInvalidArgumentExceptionProvider()
    {
        $list = [];

        foreach ($this->CacheProvider() as $provider) {
            $list[] = [$provider[0], 'getMultiple', 'foo', null];
            $list[] = [$provider[0], 'setMultiple', 'foo'];
            $list[] = [$provider[0], 'deleteMultiple', 'foo'];
        }

        return $list;
    }

    public function CacheInvalidKeysProvider()
    {
        $list = [];

        foreach ($this->CacheProvider() as $provider) {
            $list[] = [$provider[0], '{'];
            $list[] = [$provider[0], '}'];
            $list[] = [$provider[0], '('];
            $list[] = [$provider[0], ')'];
            $list[] = [$provider[0], '/'];
            $list[] = [$provider[0], '\\'];
            $list[] = [$provider[0], '@'];
            $list[] = [$provider[0], ':'];
            $list[] = [$provider[0], ''];
            $list[] = [$provider[0], null];
        }

        return $list;
    }

    /**
     * @param int $length
     * @return string
     */
    protected function getString($length = 24)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; // 62
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[rand(0, 61)];
        }
        return (string)$string;
    }
}