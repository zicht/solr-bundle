<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

/**
 * Class AbstractQueryBuilder
 */
abstract class AbstractQueryBuilder implements RequestBuilderInterface
{
    /**
     * Create a SOLR-style query string based on the specified params.
     *
     * This means that multiple values are added to the query string with the same name (which is different from
     * what `http_build_query` does)
     *
     * @param mixed[] $params
     * @return string
     */
    protected function createQueryString($params)
    {
        $ret = '';
        $isFirst = true;
        $format = function($key, $value) use(&$isFirst) {
            $ret = '';

            if (!$isFirst) {
                $ret .= '&';
            } else {
                $isFirst = false;
            }

            $ret .= rawurlencode($key) . '=' . rawurlencode($value);

            return $ret;
        };

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $ret .= $format($key, $v);
                }
            } elseif (is_scalar($value)) {
                $ret .= $format($key, $value);
            } else {
                throw new \InvalidArgumentException("Unhandled parameter type " . gettype($value) . " in call to buildQuery()");
            }
        }

        return $ret;
    }


    /**
     * This builds a JSON object to pass into SOLR.
     *
     * The SOLR server accepts structures like this:
     * ```
     * {"add": { "id": 1 }, "add": {"id": 2 }}
     * ```
     *
     * This is not possible using just json_encode, so the first level of the body is created by hand, only the values
     * are `json_encode`d.
     *
     * The instructions contains tuples of keys and values for the same reason, e.g. the above example would be
     * rendered by calling the method with
     *
     * ```
     * createRequestBody([['add', ['id' => 1]],  ['add', ['id' => '2']]])
     * ```
     *
     * @param array $instructions
     * @return string
     */
    protected function createRequestBody($instructions)
    {
        $body = '{';
        $isFirst = true;

        // The reason this is not encoded using json_encode, is that by the spec of the
        // SOLR handler, a key may occur multiple times in the same object.
        foreach ($instructions as list($key, $value)) {
            if (!$isFirst) {
                $body .= ",";
            } else {
                $isFirst = false;
            }
            $valueJson = json_encode($value);

            if (!$valueJson) {
                throw new \UnexpectedValueException("Could not convert to JSON because of an error: " . json_last_error_msg());
            }

            $body .= json_encode($key) . ':' . json_encode($value);
        }
        $body .= '}';
        return $body;
    }
}