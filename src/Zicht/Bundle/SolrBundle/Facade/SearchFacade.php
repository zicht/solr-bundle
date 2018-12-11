<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Facade;

use Zicht\Bundle\FrameworkExtraBundle\Pager\Pager;
use Zicht\Bundle\SolrBundle\Solr\Client;
use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Select;
use Zicht\Bundle\UrlBundle\Url\Params\Params;
use Zicht\Bundle\SolrBundle\Pager\SolrPageable;

/**
 * Class SearchFacade
 *
 * @package Zicht\Bundle\SolrBundle\Facade
 */
abstract class SearchFacade
{
    protected static $defaultParameterWhitelist = array('keywords', 'page', 'type', 'perpage');

    /**
     * @var Client
     */
    protected $client = null;

    /**
     * @var Params
     */
    protected $searchParams = null;

    /**
     * SOLR result document
     *
     * @var array
     */
    protected $response = null;

    /**
     * GET Url mapping
     *
     * @var null|string
     */
    protected $urlTemplate = null;

    /**
     * @var Pager
     */
    protected $pager;

    /**
     * @var int
     */
    protected $facetMinimumCount = 1;

    /**
     * @var int
     */
    protected $facetResultLimit = -1;

    /**
     * @var string
     */
    protected $facetSort = 'count';

    /**
     * @var int
     */
    protected $defaultLimit;

    /**
     * Construct the facade.
     *
     * @param Client $client
     * @param int $defaultLimit
     */
    public function __construct(Client $client, $defaultLimit = 30)
    {
        $this->client       = $client;
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
     * @param Params $params
     * @return void
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
     * @return Params
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header(sprintf('Location: %s', $this->getPostRedirect($_POST['search'])));
            exit;
        }
    }


    /**
     * Returns the GET url based on a POST search.
     *
     * @param array $postParams
     *
     * @return string
     */
    public function getPostRedirect($postParams)
    {
        $this->searchParams->mergeAll(array_filter($postParams));
        return $this->getUrl($this->searchParams);
    }


    /**
     * Adds a set of parameters to the baseUrlTemplate and returns the resultant URL
     *
     * @param Params $params
     *
     * @return string
     */
    public function getUrl($params)
    {
        return rtrim(sprintf($this->urlTemplate, $params), '/');
    }

    /**
     * Returns the page url for the specified index
     *
     * @param int    $index
     * @return string
     */
    public function getPagerUrl($index = 0)
    {
        return $this->getUrl($this->searchParams->with('page', $index, false));
    }


    /**
     * Execute the search
     *
     * @return void
     */
    final public function search()
    {
        if (!isset($this->searchParams)) {
            throw new \LogicException("You need to call setParams() first");
        }

        if (!empty($_POST['search'])) {
            $this->redirectPost($_POST['search']);
            return null;
        }

        $query = $this->createQueryBuilder();
        $this->prepareFacetSet($query);
        $this->pager = $this->initPager($query);
        $this->response = $this->execSearch($query);
    }


    /**
     * Adds the facet options to the query and apply filter queries if facets are selected.
     *
     * @param Select $query
     * @return void
     */
    protected function prepareFacetSet(Select $query)
    {
        $query
            ->setParam('facet', 'true')
            ->setParam('facet.mincount', 1)
        ;

        foreach ($this->getFacetFields() as $field) {
            $query->addFacetField($field);

            foreach ($this->searchParams->get($field) as $i => $value) {
                $query->addFilterQuery(sprintf('%s:"%s"', $field, $value));
            }
        }

        foreach ($this->getFacetQueries() as $field => $queries) {
            foreach (array_keys($queries) as $i => $filterQuery) {
                $query->addFacetQuery($query);

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
        $active = array();

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
        return $this->response->response->docs;
    }

    /**
     * @return object
     */
    public function getDebug()
    {
        return $this->response->debug;
    }

    /**
     * @return mixed
     */
    public function getNumFound()
    {
        return $this->response->response->numFound;
    }


    /**
     * @param string $name
     * @param mixed $value
     * @return int
     */
    public function getFacetCount($name, $value)
    {
        $ret = 0;
        if ($facet = $this->getResponse()->getFacetSet()->getFacet($name)) {
            $values = $facet->getValues();
            if (isset($values[$value])) {
                $ret = $values[$value];
            }
        }
        return $ret;
    }

    /**
     * Get the facet filters for use in the template
     *
     * @param null $blacklist
     * @return array
     */
    public function getFacetFilters($blacklist = null)
    {
        if (null === $blacklist) {
            $blacklist = array();
        }

        $ret = array();
        foreach ($this->getFacetFields() as $facetName) {
            if (!in_array($facetName, $blacklist)) {
                foreach (array_chunk($this->response->facet_counts->facet_fields->$facetName, 2) as list($value, $count)) {
                    $ret[$facetName][$value] = $this->getFacetMetaData($facetName, $value, $count);
                }
            }
        }

        foreach ($this->getFacetQueries() as $facetName => $facetQueries) {
            if (!in_array($facetName, $blacklist)) {
                foreach (array_values($facetQueries) as $i => $facetLabel) {
                    // This was not yet ported to the new Solarium-less implementation.
                    // It should be similar to the above implementation of the facets. Dump the response to find out
                    // dump($this->response)

                    throw new \Exception("not implemented yet: reading facet queries from response. Read source for info");

                    $count = $this->getResponse()->getFacetSet()->getFacet($facetName . '-' . $i)->getValue();
                    if ($count >= $this->facetMinimumCount) {
                        $ret[$facetName][$i] = $this->getFacetMetaData(
                            $facetName,
                            $i,
                            $count,
                            $facetLabel
                        );
                    }
                }
            }
        }
        return $ret;
    }


    /**
     * Get facet data for use in the templates for one specific facet value
     *
     * @param string $facetName
     * @param mixed $value
     * @param int|null $count
     * @param string|null $label
     * @return array
     */
    public function getFacetMetaData($facetName, $value, $count = null, $label = null)
    {
        return array(
            'value'         => $value,
            'label'         => ($label === null ? $value : $label),
            'count'         => $count,
            'active'        => $this->searchParams->contains($facetName, $value),
            'url'           => $this->getUrl($this->searchParams->without('page')->with($facetName, $value)),
            'url_filter'    => $this->getUrl($this->searchParams->without($facetName)->with($facetName, $value)),
        );
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
    public function decorateHierarchy(&$filters, $facetName, $depth = 3, $stack = array())
    {
        $ret = array();
        foreach ($filters as $i => &$term) {
            $ret[]= $term['id'];
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
     * @return array
     * @throws \LogicException
     */
    final public function getResponse()
    {
        if (!$this->response) {
            throw new \LogicException("There is no response, call search() first");
        }

        return $this->response;
    }

    /**
     * @return \Zicht\Bundle\FrameworkExtraBundle\Pager\Pager
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * Create the search query
     *
     * @return mixed
     */
    abstract protected function createQueryBuilder();

    /**
     * Return the field names that should act as a facet. Implement with an empty array return value to ignore.
     *
     * @return mixed
     */
    abstract protected function getFacetFields();

    /**
     * Return additional facet field queries. Implement with an empty array return value to ignore.
     *
     * @return array
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
        if (!!$this->response) {
            throw new \LogicException("There is already a response, call setDefaultLimit() before calling search()");
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
     * Initialize the pager.
     *
     * @param Select $query
     * @return Pager
     */
    abstract protected function initPager($query);

    /**
     * Execute the query.
     *
     * @param Select $query
     * @return \GuzzleHttp\Message\ResponseInterface
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
            foreach ($filters[$field] as $k => $details) {
                if (strcmp(strtolower($userValue), strtolower(urlencode($k)))) {
                    return true;
                }
            }
        }

        return false;
    }


    public function getHighlightedField($docId, $field)
    {
        if (isset($this->response->highlighting->$docId->$field)) {
            return $this->response->highlighting->$docId->$field;
        }

        return null;
    }
}
