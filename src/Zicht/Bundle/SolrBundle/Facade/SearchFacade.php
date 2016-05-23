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
     * @var Client $client
     * @var int $defaultLimit
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
    protected function redirectPost()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
     * @param bool $usePager
     * @return void
     *
     * @throws \LogicException
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
        $this->initPager($query);

        $this->response = $this->execSearch($query);
    }
    

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
     * @return object[]
     */
    public function getResults()
    {
        return $this->getResponse()->docs;
    }

    /**
     * @return
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
     * @param null $fields
     * @return array
     */
    public function getFacetFilters($blacklist=null)
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
                    throw new \Exception("not implemented yet");

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
     * @param string $facetName
     * @param mixed $value
     * @return array
     */
    public function getFacetMetaData($facetName, $value, $count, $label = null)
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

        return $this->response->response;
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
     * @param int $defaultLimit
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
     * @param $query
     */
    protected function initPager($query)
    {
        $currentPage = $this->searchParams->getOne('page', 0);
        $limit = $this->searchParams->getOne('limit', $this->defaultLimit);
        $this->pager = new Pager(new SolrPageable($this->client, $query), $limit);
        $this->pager->setCurrentPage($currentPage);
    }

    protected function execSearch($query)
    {
        return $this->client->select($query);
    }
}
