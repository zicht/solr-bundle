<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Facade;

use Zicht\Bundle\FrameworkExtraBundle\Pager\Pager;
use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Select;
use Zicht\Bundle\UrlBundle\Url\Params\Params;

abstract class SearchFacade
{
    protected static $defaultParameterWhitelist = ['keywords', 'page', 'type', 'perpage'];

    protected Client $client;

    /** @var Params|null */
    protected $searchParams = null;

    /** @var \stdClass|null SOLR result document */
    protected $response = null;

    /** @var string GET Url mapping */
    protected $urlTemplate = '';

    /**
     * @var Pager|null
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $pager;

    /** @var int override to 0 to keep facets displayed at all times. */
    protected $facetMinimumCount = 1;

    /** @var int */
    protected $facetResultLimit = -1;

    /** @var string */
    protected $facetSort = 'count';

    /** @var int */
    protected $defaultLimit;

    /**
     * @param int $defaultLimit
     */
    public function __construct(Client $client, $defaultLimit = 30)
    {
        $this->client = $client;
        $this->defaultLimit = $defaultLimit;
    }

    /**
     * @param string $urlTemplate
     * @return void
     */
    public function setUrlTemplate($urlTemplate)
    {
        $this->urlTemplate = $urlTemplate;
    }

    /**
     * @return void
     * @psalm-assert Params $this->searchParams
     */
    public function setParams(Params $params)
    {
        // $params contain *all* parameters, we only want to use the parameters that are relevant to the search facade
        $this->searchParams = clone $params;
        $whitelist = $this->getParameterWhitelist();

        foreach (array_keys($this->searchParams->toArray()) as $key) {
            if (!in_array($key, $whitelist)) {
                $this->searchParams->removeKey($key);
            }
        }
    }

    /**
     * @return Params|null
     * @psalm-mutation-free
     */
    public function getParams()
    {
        return $this->searchParams;
    }

    /**
     * When POST is detected, redirect to GET params
     *
     * @return void
     */
    public function redirectPost()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header(sprintf('Location: %s', $this->getPostRedirect(
                isset($_POST['search']) && is_array($_POST['search']) ? $_POST['search'] : []
            )));
            exit;
        }
    }

    /**
     * Returns the GET url based on a POST search.
     *
     * @param array $postParams
     * @return string
     */
    public function getPostRedirect($postParams)
    {
        $this->initSearchParams();
        $this->searchParams->mergeAll(array_filter($postParams));
        return $this->getUrl($this->searchParams);
    }

    /**
     * Adds a set of parameters to the baseUrlTemplate and returns the resultant URL
     *
     * @param Params $params
     * @return string
     */
    public function getUrl($params)
    {
        return rtrim(sprintf($this->urlTemplate, (string)$params), '/');
    }

    /**
     * Returns the page url for the specified index
     *
     * @param int    $index
     * @return string
     */
    public function getPagerUrl($index = 0)
    {
        $this->initSearchParams();
        return $this->getUrl($this->searchParams->with('page', (string)$index, false));
    }

    /**
     * Execute the search
     *
     * @return void
     */
    final public function search()
    {
        if (!isset($this->searchParams)) {
            throw new \LogicException('You need to call setParams() first');
        }

        if (!empty($_POST['search'])) {
            $this->redirectPost();
            return;
        }

        $query = $this->createQueryBuilder();
        $this->prepareFacetSet($query);
        $this->pager = $this->initPager($query);
        $searchResponse = $this->execSearch($query);
        $this->response = $searchResponse instanceof \stdClass ? $searchResponse : null;
    }

    /**
     * Adds the facet options to the query and apply filter queries if facets are selected.
     *
     * @return void
     */
    protected function prepareFacetSet(Select $query)
    {
        $query
            ->setParam('facet', 'true')
            ->setParam('facet.mincount', $this->facetMinimumCount);

        $this->initSearchParams();
        foreach ($this->getFacetFields() as $field) {
            $query->addFacetField($field);

            foreach ($this->searchParams->get($field) as $value) {
                $query->addFilterQuery(sprintf('%s:"%s"', $field, $value));
            }
        }

        foreach ($this->getFacetQueries() as $field => $queries) {
            foreach (array_keys($queries) as $i => $filterQuery) {
                $query->addFacetQuery($filterQuery);

                if ($this->searchParams->contains($field, $i)) {
                    $query->addFilterQuery($filterQuery);
                }
            }
        }
    }

    /**
     * @param mixed $field
     * @return array
     */
    public function getActiveFacetValues($field)
    {
        $this->initSearchParams();
        return $this->searchParams->get($field);
    }

    /**
     * Returns currently enabled and active fields through params or uri
     *
     * returns ['field1' => 'value field1', 'field2' => 'value field2']
     *
     * @return array
     */
    public function getActiveFacets()
    {
        $active = [];

        $this->initSearchParams();
        foreach ($this->getFacetFields() as $field) {
            if (($value = $this->searchParams->getOne($field)) && $this->isFacetActive($field, $value)) {
                $active[$field] = $value;
            }
        }

        return $active;
    }

    /**
     * @return object[]
     */
    public function getResults()
    {
        return $this->response ? $this->response->response->docs : [];
    }

    /**
     * @return object
     */
    public function getDebug()
    {
        return $this->response ? $this->response->debug : new \stdClass();
    }

    /**
     * @return mixed
     */
    public function getNumFound()
    {
        return $this->response ? $this->response->response->numFound : 0;
    }

    /**
     * @param string $facetName
     * @param mixed $value
     * @return int
     */
    public function getFacetCount($facetName, $value)
    {
        $count = 0;

        if (!$this->response || !isset($this->response->facet_counts->facet_fields->{$facetName})) {
            return $count;
        }
        $facet = $this->response->facet_counts->facet_fields->{$facetName};

        foreach (array_chunk($facet, 2) as [$facetValue, $facetCount]) {
            if ((string)$facetValue === (string)$value) {
                $count = $facetCount;
                break;
            }
        }

        return $count;
    }

    /**
     * Get the facet filters for use in the template
     *
     * @param string[]|null $blacklist
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function getFacetFilters($blacklist = null)
    {
        if (null === $blacklist) {
            $blacklist = [];
        }

        $ret = [];
        foreach ($this->getFacetFields() as $facetName) {
            if (!in_array($facetName, $blacklist) && isset($this->response->facet_counts->facet_fields->{$facetName})) {
                foreach (array_chunk($this->response->facet_counts->facet_fields->{$facetName}, 2) as [$value, $count]) {
                    $ret[$facetName][$value] = $this->getFacetMetaData($facetName, $value, $count);
                }
            }
        }

        foreach ($this->getFacetQueries() as $facetName => $facetQueries) {
            if (!in_array($facetName, $blacklist)) {
                foreach (array_values($facetQueries) as $_i => $_facetLabel) {
                    // This was not yet ported to the new Solarium-less implementation.
                    // It should be similar to the above implementation of the facets. Dump the response to find out
                    // dump($this->response)

                    throw new \Exception('not implemented yet: reading facet queries from response. Read source for info');
//                    $count = $this->getResponse()->getFacetSet()->getFacet($facetName . '-' . $i)->getValue();
//                    if ($count >= $this->facetMinimumCount) {
//                        $ret[$facetName][$i] = $this->getFacetMetaData(
//                            $facetName,
//                            $i,
//                            $count,
//                            $facetLabel
//                        );
//                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Get facet data for use in the templates for one specific facet value
     *
     * @param string $facetName
     * @param string $value
     * @param int|null $count
     * @param string|null $label
     * @return array
     */
    public function getFacetMetaData($facetName, $value, $count = null, $label = null)
    {
        $this->initSearchParams();
        return [
            'value' => $value,
            'label' => ($label === null ? $value : $label),
            'count' => $count,
            'active' => $this->searchParams->contains($facetName, $value),
            'url' => $this->getUrl($this->searchParams->without('page')->with($facetName, $value)),
            'url_filter' => $this->getUrl($this->searchParams->without($facetName)->with($facetName, $value)),
        ];
    }

    /**
     * Decorates a nested tree result with facet meta data recursively.
     *
     * Returns all ids that were decorated.
     *
     * @param array &$filters
     * @param string $facetName
     * @param int $depth
     * @param array $stack
     * @return array
     */
    public function decorateHierarchy(&$filters, $facetName, $depth = 3, $stack = [])
    {
        $this->initSearchParams();
        $ret = [];
        foreach ($filters as &$term) {
            $ret[] = $term['id'];
            $term += $this->getFacetMetaData($facetName, $term['id']);

            if (count($stack) < $depth && !empty($term['__children'])) {
                array_push($stack, $term);
                $descendantIds = $this->decorateHierarchy($term['__children'], $facetName, $depth, $stack);
                array_pop($stack);

                // if the term is active, and we deactivate it, all relevant children must be deactivated too
                if ($term['active']) {
                    $params = $this->searchParams->with($facetName, $term['id']);

                    foreach ($descendantIds as $descendantId) {
                        if ($params->contains($facetName, $descendantId)) {
                            $params = $params->with($facetName, $descendantId);
                        }

                        $term['url'] = $this->getUrl($params);
                    }
                }

                $ret = array_merge($ret, $descendantIds);
            }
        }

        return $ret;
    }

    /**
     * @return \stdClass
     * @throws \LogicException
     */
    final public function getResponse()
    {
        if (!$this->response) {
            throw new \LogicException('There is no response, call search() first');
        }

        return $this->response;
    }

    /**
     * @return Pager|null
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * @return mixed
     */
    abstract protected function createQueryBuilder();

    /**
     * Return the field names that should act as a facet. Implement with an empty array return value to ignore.
     *
     * @return string[]
     */
    abstract protected function getFacetFields();

    /**
     * Return additional facet field queries. Implement with an empty array return value to ignore.
     *
     * @return array<string, string[]>
     */
    abstract protected function getFacetQueries();

    /**
     * Returns the names of the parameters that should be included in the url.
     *
     * Typically this array includes
     * - all facet field names
     * - the search keywords name
     *
     * This whitelist is used when the search page is called with, for example:
     * /search?foo=bar
     * In this example we do *not* want the parameter "foo" to be included in any generated url.
     *
     * @return array
     */
    protected function getParameterWhitelist()
    {
        return array_merge(
            $this->getFacetFields(),
            array_keys($this->getFacetQueries()),
            self::$defaultParameterWhitelist
        );
    }

    /**
     * Override the default limit
     *
     * @param int $defaultLimit
     * @return void
     */
    public function setDefaultLimit($defaultLimit)
    {
        if ((bool)$this->response) {
            throw new \LogicException('There is already a response, call setDefaultLimit() before calling search()');
        }

        $this->defaultLimit = $defaultLimit;
    }

    /**
     * @return int
     */
    public function getDefaultLimit()
    {
        return $this->defaultLimit;
    }

    /**
     * @psalm-assert Params $this->searchParams
     */
    private function initSearchParams(): void
    {
        if (!isset($this->searchParams)) {
            $this->searchParams = new Params();
        }
    }

    /**
     * @param Select $query
     * @return Pager|null
     */
    abstract protected function initPager($query);

    /**
     * @param Select $query
     * @return mixed
     */
    protected function execSearch($query)
    {
        return $this->client->select($query);
    }

    /**
     * Check user input against retrieved values
     *
     * @param string $field
     * @param string $userValue
     * @return bool
     */
    public function isFacetActive($field, $userValue)
    {
        $filters = $this->getFacetFilters();

        if (isset($filters[$field]) && sizeof($filters[$field])) {
            foreach (array_keys($filters[$field]) as $k) {
                if (strcmp(strtolower($userValue), strtolower(urlencode($k)))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find highlighting by trying multiple fields
     *
     * @param string $docId
     * @param string[] $fields
     * @return string[]|null
     */
    public function getHighlightedFieldFromFieldList($docId, $fields)
    {
        foreach ($fields as $field) {
            $highlight = $this->getHighlightedField($docId, $field);
            if (null !== $highlight) {
                return $highlight;
            }
        }

        return null;
    }

    /**
     * @param string $docId
     * @param string $field
     * @return string[]|null
     */
    public function getHighlightedField($docId, $field)
    {
        if (isset($this->response->highlighting->{$docId}->{$field})
            && is_array($this->response->highlighting->{$docId}->{$field})
            && count($this->response->highlighting->{$docId}->{$field}) > 0) {
            return $this->response->highlighting->{$docId}->{$field};
        }

        return null;
    }
}
