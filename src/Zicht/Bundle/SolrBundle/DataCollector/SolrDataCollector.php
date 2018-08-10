<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DataCollector;

use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class SolrDataCollector
 *
 * @package Zicht\Bundle\SolrBundle\DataCollector
 */
class SolrDataCollector extends DataCollector
{
    /** @var StreamInterface[]  */
    private $stack = [];

    /**
     * @param $name
     * @param StreamInterface $stack
     */
    public function addDebugger($name, StreamInterface $stack)
    {
        $this->stack[$name] = $stack;
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request    $request   A Request instance
     * @param Response   $response  A Response instance
     * @param \Exception $exception An Exception instance
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if (!empty($this->stack)) {
            foreach ($this->stack as $name => $data) {
                switch ($name) {
                    case 'requests':
                        $this->data[$name] = $this->fmtRequest((string)$data);
                        break;
                    default:
                        $this->data[$name] = (string)$data;
                }
            }
        }
    }

    /**
     * @param string $s
     * @return array
     */
    private function fmtRequest($s)
    {
        $ret = [];

        foreach (explode('* EOT', $s) as $req) {
            if (empty(trim($req))) {
                continue;
            }

            $parts = explode('>> ', $req);
            $header = array_shift($parts);
            $request = '';
            $response = '';
            $current = &$request;
            $footer = '';

            while ($part = array_shift($parts)) {
                if (false !== $index = strpos($part, '<<')) {
                    $current.= rtrim(substr($part, 0, $index));
                    $parts = explode('<< ', substr($part, $index+3));
                    $current = &$response;
                } else {
                    $current .= $part;
                }
            }


            if (false !== $index = strpos($response, '* finished')) {
                $footer = substr($response, $index);
                $response = substr($response, 0, $index);
            }

            $ret[] = [
                'header' => $header,
                'request' => $request,
                'request_vars' => $this->getQuery($request),
                'response' => $response,
                'response_vars' => $this->getBody($response),
                'footer' => $footer,
                'time' => $this->getTime($footer) - $this->getTime($header)
            ];
        }

        return array_filter($ret);
    }

    /**
     * @param string $s
     * @return array
     */
    private function getQuery($s)
    {
        if (preg_match('/^[^\s]+\s([^\s]+)\sHTTP\//', $s, $m)) {
            $parts = parse_url($m[1]);
            $query = [];
            $request = $this->getBody($s);

            if (isset($parts['query'])) {
                foreach (explode('&', $parts['query']) as $q) {
                    list($key, $value) = array_map('urldecode', explode('=', $q, 2));

                    if (isset($query[$key]) && is_array($query[$key])) {
                        $query[$key][] = $value;
                        continue;
                    }

                    if (isset($query[$key]) && is_scalar($query[$key])) {
                        $query[$key] = [$query[$key], $value];
                        continue;
                    }
                    $query[$key]= $value;
                }
            }

            return [
                'path' => isset($parts['path']) ? $parts['path'] : '',
                'query' => $query,
                'request' => $request,
            ];
        }
        return [
            'path' => '',
            'query' => [],
            'request' => [],
        ];
    }

    /**
     * @param string $s
     * @return array|mixed
     */
    private function getBody($s)
    {
        if (preg_match('/(\{.*\})$/is', $s, $m)) {
            return json_decode($m[1]);
        }

        return [];
    }

    /**
     * @param string $s
     * @return float
     */
    private function getTime($s)
    {
        if (preg_match('/(?:finished|start) (\d+\.\d+)/', $s, $m)) {
            return (float)$m[1];
        }

        return (float)0;
    }

    /**
     * @return \Generator
     */
    public function getRequests()
    {

        if (isset($this->data['requests'])) {
            foreach ($this->data['requests'] as $request) {
                yield $request;
            }
        }
    }

    /**
     * @return int
     */
    public function getCountRequests()
    {
        return (isset($this->data['requests'])) ? count($this->data['requests']) : 0;
    }

    /**
     * @return float|int
     */
    public function getTimeRequests()
    {
        if (!isset($this->data['requests'])) {
            return 0;
        }

        return array_sum(array_column($this->data['requests'], 'time')) * 1000;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'zicht_solr.data_collector';
    }
}
