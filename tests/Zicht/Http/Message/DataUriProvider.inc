<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

return [
    [
        'http://127.0.0.1/foo/bar',
        [
            'scheme' => 'http',
            'authority' => '127.0.0.1',
            'host' => '127.0.0.1',
            'path' => '/foo/bar',
        ]
    ],
    [
        'http://127.0.0.1:80/foo/]/%5D',
        [
            'scheme' => 'http',
            'authority' => '127.0.0.1',
            'host' => '127.0.0.1',
            'path' => '/foo/%5D/%5D',
        ]
    ],
    [
        'http://127.0.0.1:8080/foo/*',
        [
            'scheme' => 'http',
            'authority' => '127.0.0.1:8080',
            'host' => '127.0.0.1',
            'port' => 8080,
            'path' => '/foo/%2A',
        ]
    ],
    [
        'https://127.0.0.1/hello world',
        [
            'scheme' => 'https',
            'authority' => '127.0.0.1',
            'host' => '127.0.0.1',
            'path' => '/hello%20world',
        ]
    ],
    [
        'https://127.0.0.1:443/%2F%2F%2F/bar',
        [
            'scheme' => 'https',
            'authority' => '127.0.0.1',
            'host' => '127.0.0.1',
            'path' => '/%2F%2F%2F/bar'
        ]
    ],
    [
        'https://127.0.0.1:8080/hello%20world',
        [
            'scheme' => 'https',
            'authority' => '127.0.0.1:8080',
            'host' => '127.0.0.1',
            'port' => 8080,
            'path' => '/hello%20world',
        ]
    ],
    [
        'http://user@127.0.0.1:80?a=b&c=hello world&d=hello%20world&e=//&f[]=1&f[]=%26',
        [
            'scheme' => 'http',
            'authority' => 'user@127.0.0.1',
            'user_info' => 'user',
            'host' => '127.0.0.1',
            'query' => 'a=b&c=hello%20world&d=hello%20world&e=%2F%2F&f%5B%5D=1&f%5B%5D=%26'
        ]
    ],
    [
        'http://user:password@127.0.0.1:80#hello world',
        [
            'scheme' => 'http',
            'authority' => 'user:password@127.0.0.1',
            'user_info' => 'user:password',
            'host' => '127.0.0.1',
            'fragment' => 'hello%20world'
        ]
    ],
    [
        'http://user:password@127.0.0.1:8080#hello%20world',
        [
            'scheme' => 'http',
            'authority' => 'user:password@127.0.0.1:8080',
            'user_info' => 'user:password',
            'host' => '127.0.0.1',
            'port' => 8080,
            'fragment' => 'hello%20world'
        ]
    ],
    [
        'https://user:password@127.0.0.1:443#*',
        [
            'scheme' => 'https',
            'authority' => 'user:password@127.0.0.1',
            'user_info' => 'user:password',
            'host' => '127.0.0.1',
            'fragment' => '%2A'
        ]
    ],
    [
        'https://user:password@127.0.0.1:8080',
        [
            'scheme' => 'https',
            'authority' => 'user:password@127.0.0.1:8080',
            'user_info' => 'user:password',
            'host' => '127.0.0.1',
            'port' => 8080,
        ]
    ]
];