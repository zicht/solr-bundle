<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class Select extends AbstractQueryBuilder implements ResponseHandlerInterface
{
    /** @var array<string, scalar|scalar[]> */
    protected $params = [
        'wt' => 'json',
        'q' => '*:*',
        'fl' => '*,score',
    ];

    /**
     * Set the 'q' parameter
     *
     * @param string $q
     * @return $this
     */
    public function setQuery($q)
    {
        return $this->setParam('q', $q);
    }

    /**
     * Set the field list '`fl` parameter
     *
     * @param string $fl
     * @return $this
     */
    public function setFieldList($fl)
    {
        return $this->setParam('fl', $fl);
    }

    /**
     * Sets the `start` parameter (the paging offset)
     *
     * @param int $start
     * @return $this
     */
    public function setStart($start)
    {
        return $this->setParam('start', (int)$start);
    }

    /**
     * Sets the `rows` parameter (number of rows to return)
     *
     * @param int $rows
     * @return $this
     */
    public function setRows($rows)
    {
        return $this->setParam('rows', (int)$rows);
    }

    /**
     * Set an arbitrary parameter value
     *
     * @param string $param
     * @param mixed $value
     * @return $this
     */
    public function setParam($param, $value)
    {
        $this->params[$param] = $value;

        return $this;
    }

    /**
     * Adds an `fq` (filter query) parameter.
     *
     * @param string $fq
     * @return $this
     */
    public function addFilterQuery($fq)
    {
        return $this->addParam('fq', $fq);
    }

    /**
     * Sets the `fq` (filter query) parameter. Clears any previously set filter query.
     *
     * @param string $fq
     * @return $this
     */
    public function setFilterQuery($fq)
    {
        return $this->setParam('fq', $fq);
    }

    public function createRequest(ClientInterface $httpClient)
    {
        return new Request('GET', sprintf('%sselect?%s', $httpClient->getConfig('base_uri'), $this->createQueryString($this->params)));
    }

    public function handle(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        if (preg_match('!^application/json!', $contentType) || preg_match('!^text/plain!', $contentType)) {
            $response = json_decode($response->getBody()->getContents());
        }
        return $response;
    }

    /**
     * Adds an arbitrary parameter to the request. This adds a value to the existing parameter value (if any).
     *
     * Only useful for multiple valued parameters, such as 'fq', or 'facet.query', etc
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addParam($name, $value)
    {
        if (!isset($this->params[$name])) {
            $this->params[$name] = [];
        } elseif (!is_array($this->params[$name])) {
            $this->params[$name] = [$this->params[$name]];
        }
        $this->params[$name][] = $value;

        return $this;
    }

    /**
     * Sets the `defType` parameter.
     *
     * @param string $string
     * @return $this
     */
    public function setDefType($string)
    {
        return $this->setParam('defType', $string);
    }

    /**
     * Sets the `qf` (queryfields) parameter. This would usually be used in conjunction with `setDefType('edismax')`,
     * for example.
     *
     * @param string $string
     * @return $this
     */
    public function setQueryFields($string)
    {
        return $this->setParam('qf', $string);
    }

    /**
     * Sets the sort parameter
     *
     * @param string|array $value
     * @return $this
     */
    public function setSort($value)
    {
        return $this->setParam('sort', $value);
    }

    /**
     * Set the `facet.mincount` value.
     *
     * @param int $value
     * @return $this
     */
    public function setFacetMinCount($value)
    {
        return $this->setParam('facet.mincount', (int)$value);
    }

    /**
     * Adds a `facet.field` parameter
     *
     * @param string $field
     * @return $this
     */
    public function addFacetField($field)
    {
        return $this->addParam('facet.field', $field);
    }

    /**
     * Adds a `facet.query` parameter
     *
     * @param string $query
     * @return $this
     */
    public function addFacetQuery($query)
    {
        return $this->addParam('facet.query', $query);
    }

    /**
     * Set the `group.field` value
     *
     * @param string $groupField
     * @return $this
     */
    public function setGroupField($groupField)
    {
        return $this->setParam('group.field', $groupField);
    }

    /**
     * Set the `group.limit` field
     *
     * @param string $groupLimit
     * @return $this
     */
    public function setGroupLimit($groupLimit)
    {
        return $this->setParam('group.limit', $groupLimit);
    }

    /**
     * Set debugging on.
     *
     * @param mixed $value
     * @param bool $structured
     * @return $this
     */
    public function setDebugQuery($value, $structured = false)
    {
        $this->setParam('debugQuery', $value);
        if ($structured) {
            $this->setParam('debug.explain.structured', 'true');
        }

        return $this;
    }
}
