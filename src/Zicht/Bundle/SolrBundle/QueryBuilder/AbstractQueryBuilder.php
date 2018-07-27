<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\QueryBuilder;

/**
 * Class AbstractQueryBuilder
 */
abstract class AbstractQueryBuilder implements RequestBuilderInterface
{
    /**
     * Create a SOLR-style query string based on the specified params.
     *
     * This means that multiple values are added to the query string with
     * the same name (which is different from what `http_build_query` does)
     *
     * @param mixed[] $params
     * @return string
     */
    protected function createQueryString($params)
    {
        $ret = '';

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $ret .= '&' .rawurlencode($key) . '=' . rawurlencode($v);
                }
            } elseif (is_scalar($value)) {
                $ret .= '&'. rawurlencode($key) . '=' . rawurlencode($value);
            } else {
                throw new \InvalidArgumentException("Unhandled parameter type " . gettype($value) . " in call to buildQuery()");
            }
        }

        return substr($ret, 1);
    }
}