<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\QueryBuilder;

use Psr\Http\Message\ResponseInterface;
use Zicht\Http\RequestFactoryInterface;

/**
 * Class Select
 */
class Select extends AbstractQueryBuilder implements ResponseHandlerInterface
{
    /**
     * @var array
     */
    protected $params = [
        'wt' => 'json',
        'q' => '*:*',
        'fl' => '*,score'
    ];


    /**
     * Set the 'q' parameter
     *
     * @param string $q
     * @return $this|Select
     */
    public function setQuery($q)
    {
        return $this->setParam('q', $q);
    }

    /**
     * Set the field list '`fl` parameter
     *
     * @param string $fl
     * @return $this|Select
     */
    public function setFieldList($fl)
    {
        return $this->setParam('fl', $fl);
    }


    /**
     * Sets the `start` parameter (the paging offset)
     *
     * @param int $start
     * @return $this|Select
     */
    public function setStart($start)
    {
        $this->setParam('start', (int)$start);

        return $this;
    }


    /**
     * Sets the `rows` parameter (number of rows to return)
     *
     * @param int $rows
     * @return $this
     */
    public function setRows($rows)
    {
        $this->setParam('rows', (int)$rows);

        return $this;
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
        $this->addParam('fq', $fq);

        return $this;
    }


    /**
     * Sets the `fq` (filter query) parameter. Clears any previously set filter query.
     *
     * @param string $fq
     * @return $this
     */
    public function setFilterQuery($fq)
    {
        $this->setParam('fq', $fq);

        return $this;
    }

    /**
     * @{inheritDoc}
     */
    public function createRequest(RequestFactoryInterface $factory)
    {
        return $factory->createRequest('GET', 'select?' . $this->createQueryString($this->params));
    }

    /**
     * @{inheritDoc}
     */
    public function handle(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('content-type');

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
     * @param string $value
     * @return $this
     */
    public function addParam($name, $value)
    {
        if (!isset($this->params[$name])) {
            $this->params[$name] = [];
        } elseif (!is_array($this->params[$name])) {
            $this->params[$name] = [$this->params[$name]];
        }
        $this->params[$name][]= $value;

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
        $this->setParam('defType', $string);

        return $this;
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
        $this->setParam('qf', $string);

        return $this;
    }

    /**
     * Sets the sort parameter
     *
     * @param string $string
     * @return $this
     */
    public function setSort($string)
    {
        $this->setParam('sort', $string);

        return $this;
    }

    /**
     * Set the `facet.mincount` value.
     *
     * @param int $value
     * @return $this
     */
    public function setFacetMinCount($value)
    {
        $this->setParam('facet.mincount', (int)$value);

        return $this;
    }

    /**
     * Adds a `facet.field` parameter
     *
     * @param string $field
     * @return $this
     */
    public function addFacetField($field)
    {
        $this->addParam('facet.field', $field);

        return $this;
    }

    /**
     * Adds a `facet.query` parameter
     *
     * @param string $query
     * @return $this
     */
    public function addFacetQuery($query)
    {
        $this->addParam('facet.query', $query);

        return $this;
    }

    /**
     * Set the `group.field` value
     *
     * @param string $groupField
     * @return $this
     */
    public function setGroupField($groupField)
    {
        $this->setParam('group.field', $groupField);

        return $this;
    }


    /**
     * Set the `group.limit` field
     *
     * @param string $groupLimit
     * @return $this
     */
    public function setGroupLimit($groupLimit)
    {
        $this->setParam('group.limit', $groupLimit);

        return $this;
    }


    /**
     * Set debugging on.
     *
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