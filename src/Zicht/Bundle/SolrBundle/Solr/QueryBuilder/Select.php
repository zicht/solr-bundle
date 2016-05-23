<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;


use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;

class Select extends AbstractQueryBuilder implements ResponseHandlerInterface
{
    protected $params = [
        'wt' => 'json',
        'q' => '*:*',
        'fl' => '*,score'
    ];


    public function setQuery($q)
    {
        return $this->setParam('q', $q);
    }

    public function setFieldList($fl)
    {
        return $this->setParam('fl', $fl);
    }


    public function setStart($start)
    {
        return $this->setParam('start', (int)$start);
    }


    public function setRows($rows)
    {
        $this->setParam('rows', (int)$rows);

        return $this;
    }


    public function setParam($param, $value)
    {
        $this->params[$param] = $value;

        return $this;
    }

    public function addFilterQuery($fq)
    {
        $this->addParam('fq', $fq);

        return $this;
    }


    public function setFilterQuery($fq)
    {
        $this->setParam('fq', $fq);

        return $this;
    }

    public function createRequest(Client $client)
    {
        return $client->createRequest('GET', 'select?' . $this->createQueryString($this->params));
    }

    public function handle(Response $response)
    {
        $contentType = $response->getHeader('Content-Type');
        if (preg_match('!^application/json!', $contentType) || preg_match('!^text/plain!', $contentType)) {
            $response = json_decode($response->getBody()->getContents());
        }
        return $response;
    }

    private function addParam($name, $value)
    {
        if (!isset($this->params[$name])) {
            $this->params[$name] = [];
        } elseif (!is_array($this->params[$name])) {
            $this->params[$name] = [$this->params[$name]];
        }
        $this->params[$name][]= $value;

        return $this;
    }

    public function setDefType($string)
    {
        $this->setParam('defType', $string);

        return $this;
    }

    public function setQueryFields($string)
    {
        $this->setParam('qf', $string);

        return $this;
    }

    public function setSort($string)
    {
        $this->setParam('sort', $string);

        return $this;
    }

    public function setFacetMinCount($value)
    {
        $this->setParam('facet.mincount', (int) $value);

        return $this;
    }

    public function addFacetField($field)
    {
        $this->addParam('facet.field', $field);

        return $this;
    }

    public function addFacetQuery($query)
    {
        $this->addParam('facet.query', $query);

        return $this;
    }

    public function setGroupField($groupField)
    {
        $this->setParam('group.field', $groupField);

        return $this;
    }


    public function setGroupLimit($groupLimit)
    {
        $this->setParam('group.limit', $groupLimit);

        return $this;
    }
}