<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Http\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Zicht\Http\Exception\ConnectionException;
use Zicht\Http\Exception\ResponseException;
use Zicht\Http\Stream\ResourceStream;
use Zicht\Http\Stream\WrappedDebugStream;
use Zicht\Http\Message\Response;

/**
 * Class SocketHandler
 *
 * @package Zicht\Http\Handler
 */
class SocketHandler implements HandlerInterface, HandlerDebugInterface
{
    use HandlerDebugTrait;

    /** @var UriInterface */
    private $host;
    /** @var array  */
    private $options;

    /**
     * SocketAdapter constructor.
     *
     * @param UriInterface $host
     * @param array $options
     */
    public function __construct(UriInterface $host, array $options = [])
    {
        $this->host = $host;
        $this->options = $options;
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function send(RequestInterface $request)
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme() ?: 'http';
        list($host, $port) = $this->getHostPost($uri);
        $this->log('* trying ' . $host . '...');
        $this->log('* start ' . microtime(true));
        $conn = $this->connect($host, $port);
        $this->log('* connected to "' . $scheme . '://' . $host . ':' . $port . '"');
        $this->writeRequest($conn, $request);
        $resp = $this->readResponse($conn);
        $conn->close();
        $this->log('* finished ' . microtime(true));
        $this->log("* EOT");
        return $resp;
    }

    /**
     * @param StreamInterface $writer
     * @param RequestInterface $request
     */
    public function writeRequest(StreamInterface $writer, RequestInterface $request)
    {
        $header = ['Connection' => 'close'];
        $size = 0;
        if (null !== $request->getBody() && (($size = $request->getBody()->getSize()) && $size > 0)) {
            $header['Content-Length'] = $size;
        }
        $writer->write($this->getRequestLine($request));
        foreach ($this->getHeaders($request, $header) as $name => $value) {
            $writer->write(sprintf("%s: %s\r\n", $name, $value));
        }
        $writer->write("\r\n");
        if ($size > 0) {
            while (!$request->getBody()->eof()) {
                $writer->write($request->getBody()->read(1024));
            }
        }
    }

    /**
     * @param StreamInterface $stream
     * @return Response
     */
    private function readResponse(StreamInterface $stream)
    {
        $state = 0;
        $buf = '';
        $code = $protocol = $phrase = $body = null;
        $headers = [];
        $fd = fopen('php://temp', 'r+');

        while (!$stream->eof() || strlen($buf) > 0) {
            if ($state < 2) {
                $buf .= $stream->read(64);
                while (false !== $index = strpos($buf, "\r\n")) {
                    $line = substr($buf, 0, $index);
                    switch ($state) {
                        case 0: // request line
                            list($code, $protocol, $phrase) = $this->parseRequestLine($line);
                            $state++;
                            break;
                        case 1: // header
                            if (empty($line)) {
                                $state++;
                            } else {
                                list($name, $value) = $this->parseHeader($line);
                                $headers[$name][] = $value;
                            }
                            break;
                    }
                    $buf = substr($buf, $index + 2);
                }
            } else {
                if (!empty($buf)) {
                    fwrite($fd, $buf);
                    $buf = null;
                }
                fwrite($fd, $stream->read(1024));
            }
        }

        if (($pos = ftell($fd)) && $pos > 0) {
            ftruncate($fd, $pos - 1);
            fseek($fd, 0);
            $body = new ResourceStream($fd);
        }

        return new Response($code, $phrase, $protocol, $headers, $body);

    }

    /**
     * @param RequestInterface $request
     * @return string
     */
    private function getRequestLine(RequestInterface $request)
    {
        $target = $request->getRequestTarget();
        if ('/' !== $target[0]) {
            $target = sprintf('%s/%s', rtrim($this->host->getPath(), '/'),  $target);
        }
        return sprintf("%s %s HTTP/%s\r\n", $request->getMethod(), $target, $request->getProtocolVersion());
    }

    /**
     * @param string $line
     * @return array|string[]
     */
    private function parseRequestLine($line)
    {
        if (!preg_match('`^HTTP/(?P<protocol>[^\s]+)\s(?P<code>\d{3})(?:\s(?P<phrase>[^$]+))?$`', $line, $match)) {
            throw new ResponseException(sprintf('Invalid request line "%s"', $line));
        }
        if (isset($match['phrase'])) {
            return [$match['code'], $match['protocol'], $match['phrase']];
        } else {
            return [$match['code'], $match['protocol'], null];
        }
    }

    /**
     * @param RequestInterface $request
     * @param array $headers
     * @return \Generator|string[]
     */
    private function getHeaders(RequestInterface $request, array $headers = [])
    {
        foreach ($this->getHeaderNames($request) as $name) {
            $headers[$this->fmtHeaderName($name)] = $request->getHeaderLine($name);
        }
        ksort($headers);
        if (isset($headers['Host'])) {
            yield 'Host' => $headers['Host'];
        } else {
            yield 'Host' => sprintf('%s', $request->getUri()->getAuthority() ?: $this->host->getAuthority());
        }
        foreach ($headers as $name => $header) {
            if ('Host' === $name) {
                continue;
            }
            yield $name => $header;
        }
    }

    /**
     * @param string $line
     * @return array|string[]
     */
    private function parseHeader($line)
    {
        if (false == preg_match('`^(?P<name>[^:]+):\s(?P<value>[^$]+)$`', $line, $match)) {
            throw new ResponseException(sprintf('Invalid header line "%s"', $line));
        }
        return [$match['name'], $match['value']];
    }

    /**
     * @param RequestInterface $request
     * @return array
     */
    private function getHeaderNames(RequestInterface $request)
    {
        return array_unique(array_map('strtolower', array_keys($request->getHeaders())));
    }

    /**
     * @param string $name
     * @return string string
     */
    private function fmtHeaderName($name)
    {
        return implode('-', array_map('ucfirst', explode('-', $name)));
    }

    /**
     * @return StreamInterface
     */
    protected function connect($host, $port)
    {
        if (isset($this->options['timeout'])) {
            $fp = fsockopen($host, $port, $errno, $errstr, $this->options['timeout']);
        } else {
            $fp = fsockopen($host, $port, $errno, $errstr);
        }
        if (false === $fp) {
            throw new ConnectionException(sprintf('Failed to connect to %s:%s, %s', $host, $port, $errstr), $errno);
        }
        $stream = new ResourceStream($fp);
        return ($this->isDebug()) ? new WrappedDebugStream($stream , $this->getLog()) : $stream;
    }


    /**
     * @param UriInterface $uri
     * @return array
     */
    private function getHostPost(UriInterface $uri)
    {
        $host = $uri->getHost();
        $port = $uri->getPort();
        $scheme = $uri->getScheme();
        if (empty($host)) {
            $host = $this->host->getHost();
            $port = $this->host->getPort();
            $scheme = $this->host->getScheme();
        }
        if (empty($port)) {
            switch ($scheme) {
                case 'https':
                    $port = 443;
                    $host = "tls://$host";
                    break;
                default:
                    $port = 80;

            }
        }
        return [$host, $port];
    }
}