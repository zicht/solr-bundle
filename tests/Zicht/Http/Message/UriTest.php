<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Message;

/**
 * Class UriTest
 *
 * test the psr7 implementation
 *
 * https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface
 *
 * @package Zicht\SimpleCache
 */
class UriTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider InvalidUriProvider
     * @expectedException \Zicht\Http\Exception\InvalidArgumentException
     */
    public function testInvalidUrlException($url)
    {
        (new Uri($url));
    }

    /**
     * @dataProvider UriProvider
     */
    public function testScheme($url, $info)
    {
        $this->assertSame($info['scheme'], (new Uri($url))->getScheme());
    }

    /**
     * @dataProvider UriProvider
     */
    public function testAuthority($url, $info)
    {
        $this->assertSame($info['authority'], (new Uri($url))->getAuthority());
    }

    /**
     * @dataProvider UriProvider
     */
    public function testUserInfo($url, $info)
    {
        $this->assertSame(isset($info['user_info']) ? $info['user_info'] : '', (new Uri($url))->getUserInfo());
    }

    /**
     * @dataProvider UriProvider
     */
    public function testHost($url, $info)
    {
        $this->assertSame(isset($info['host']) ? $info['host'] : '', (new Uri($url))->getHost());
    }

    /**
     * @dataProvider UriProvider
     */
    public function testPort($url, $info)
    {
        $this->assertSame(isset($info['port']) ? (int)$info['port'] : null, (new Uri($url))->getPort());
    }

    /**
     * @dataProvider UriProvider
     */
    public function testPath($url, $info)
    {
        $this->assertSame(isset($info['path']) ? $info['path'] : '', (new Uri($url))->getPath());
    }

    /**
     * @dataProvider UriProvider
     */
    public function testQuery($url, $info)
    {
        $this->assertSame(isset($info['query']) ? $info['query'] : '', (new Uri($url))->getQuery());
    }

    /**
     * @dataProvider UriProvider
     */
    public function testFragment($url, $info)
    {
        $this->assertSame(isset($info['fragment']) ? $info['fragment'] : '', (new Uri($url))->getFragment());
    }

    /**
     * @dataProvider UriProvider
     */
    public function testWithScheme($url, $info)
    {
        $uri = new Uri($url);
        $new = $uri->withScheme($info['scheme'] === 'http' ? 'HTTPS' : 'HTTP');
        $this->assertNotSame($uri, $new);
        $this->assertSame(($info['scheme'] === 'http') ? 'https' : 'http', $new->getScheme());
        $this->assertSame('', $new->withScheme('')->getScheme());
        $this->assertSame('', $new->withScheme(null)->getScheme());
    }

    public function testWithUserInfo()
    {
        $uri = new Uri('http://foo:bar@127.0.0.1');
        $new = $uri->withUserInfo('a', 'b');
        $this->assertNotSame($uri, $new);
        $this->assertSame('a:b', $new->getUserInfo());
        $new = $uri->withUserInfo('a');
        $this->assertSame('a', $new->getUserInfo());
        $new = $uri->withUserInfo(null);
        $this->assertSame('', $new->getUserInfo());
    }

    private function newWith(Uri $uri, $name, $value)
    {
        $new = call_user_func([$uri, 'with' . $name], $value);
        $this->assertNotSame($uri, $new);
        $this->assertSame($value, call_user_func([$new, 'get' . $name]));
        return $new;
    }

    public function testWithHost()
    {
        $uri = new Uri('http://127.0.0.1');
        $new = $this->newWith($uri, 'Host', 'example.com');
        $this->assertSame('', $new->withHost('')->getHost());
        $this->assertSame('', $new->withHost(null)->getHost());
    }

    public function testWithPort()
    {
        $uri = new Uri('http://127.0.0.1');
        $new = $this->newWith($uri, 'Port', 8080);
        $this->assertSame(null, $new->withPort(null)->getPort());
    }

    /**
     * @expectedException \Zicht\Http\Exception\InvalidArgumentException
     */
    public function testWithInvalidPort()
    {
        (new Uri('http://127.0.0.1'))->withPort((2**16));
    }


    public function testWithPath()
    {
        $uri = new Uri('http://127.0.0.1');
        $new = $this->newWith($uri, 'Path', '/bar/foo');
        $this->assertSame('foo/bar', $new->withPath('foo/bar')->getPath());
        $this->assertSame('', $new->withPath('')->getPath());
        $this->assertSame('', $new->withPath(null)->getPath());
    }

    public function testWithQuery()
    {
        $uri = new Uri('http://127.0.0.1');
        $new = $this->newWith($uri, 'Query', 'a=b');
        $this->assertSame('a=hello%20world', $new->withQuery('a=hello world')->getQuery());
        $this->assertSame('a=hello%20world', $new->withQuery('a=hello%20world')->getQuery());
        $this->assertSame('', $new->withQuery('')->getQuery());
        $this->assertSame('', $new->withQuery(null)->getQuery());
    }

    public function testWithFragment()
    {
        $uri = new Uri('http://127.0.0.1');
        $new = $this->newWith($uri, 'Fragment', 'example-fragment');
        $this->assertSame('hello%20%20world', $new->withFragment('hello %20world')->getFragment());
        $this->assertSame('hello%20world', $new->withFragment('hello%20world')->getFragment());
        $this->assertSame('', $new->withFragment('')->getFragment());
        $this->assertSame('', $new->withFragment(null)->getFragment());
    }

    public function testString()
    {
        $this->assertSame('http://example.com', (string)(new Uri('http://example.com')));
        $this->assertSame('http://example.com/hello%20world', (string)(new Uri('http://example.com'))->withPath('hello world'));
        $this->assertSame('http://example.com/hello%20world', (string)(new Uri('http://example.com'))->withPath('//hello world'));
        $this->assertSame('http://example.com?a=b', (string)(new Uri('http://example.com'))->withQuery('a=b'));
        $this->assertSame('http://example.com#ab', (string)(new Uri('http://example.com'))->withFragment('ab'));

    }


    /**
     * @return array
     */
    public function InvalidUriProvider()
    {
        return [
            ['ssl://127.0.0.1'],
            ['tcp://127.0.0.1'],
            ['udp://127.0.0.1'],
            ['rcp://127.0.0.1'],
        ];
    }

    /**
     * @return array
     */
    public function UriProvider()
    {
        static $data;
        if (!$data) {
            $data = require_once 'DataUriProvider.inc';
        }
        return $data;
    }
}