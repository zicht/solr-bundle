<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Facade;

use \Solarium\Core\Client\Client;

use \Zicht\Bundle\UrlBundle\Url\Params\Params;
use \Zicht\Bundle\FrameworkExtraBundle\Pager\Pager;
use \Zicht\Bundle\SolrBundle\Pager\SolrPageable;

/**
 * Class SearchFacade
 *
 * @package Zicht\Bundle\SolrBundle\Facade
 */
abstract class SearchFacade
{
    /**
     * @var \Solarium\Core\Client\Client
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
     * Construct the facade.
     *
     * @param \Solarium\Core\Client\Client $client
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
        $this->searchParams = $params;
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

        /**
         * @var $query \Solarium\QueryType\Select\Query\Query
         */
        $query = $this->createQuery();

        $facetFields = $this->getFacetFields();
        $this->prepareFacetFieldQuery($facetFields, $query);

        $currentPage = $this->searchParams->getOne('page', 0);
        $limit = $this->searchParams->getOne('limit', $this->defaultLimit);
        $this->pager = new Pager(new SolrPageable($this->client, $query), $limit);
        $this->pager->setCurrentPage($currentPage);

        $this->response = $this->client->select($query);
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
     * @return array
     */
    public function getResults()
    {
        return $this->getResponse();
    }

    /**
     * @return mixed
     */
    public function getNumFound()
    {
        return $this->response->getNumFound();
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
    public function getFacetFilters($fields = null)
    {
        if (null === $fields) {
            $fields = $this->getFacetFields();
        }

        $ret = array();
        foreach ($fields as $facetName) {
            foreach ($this->getResponse()->getFacetSet()->getFacet($facetName)->getValues() as $value => $count) {
                $ret[$facetName][$value] = $this->getFacetMetaData($facetName, $value);
            }
        }
        return $ret;
    }


    /**
     * @param string $facetName
     * @param mixed $value
     * @return array
     */
    public function getFacetMetaData($facetName, $value)
    {
        return array(
            'value'         => $value,
            'count'         => $this->getFacetCount($facetName, $value),
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
    abstract protected function createQuery();

    /**
     * Return the field names that should act as a facet
     *
     * @return mixed
     */
    abstract protected function getFacetFields();

    /**
     * Prepare the facet field query
     *
     * @param array $facetFields
     * @param string $query
     * @return void
     */
    protected function prepareFacetFieldQuery($facetFields, $query)
    {
        if (count($facetFields)) {
            // Setup facetting
            /** @var $facetSet \Solarium\QueryType\Select\Query\Component\FacetSet */
            $facetSet = $query->getFacetSet();
            $facetSet
                ->setMinCount($this->facetMinimumCount)
                ->setLimit($this->facetResultLimit);
            foreach ($facetFields as $field) {
                $facetSet->createFacetField($field)->setField($field);

                foreach ($this->searchParams->get($field) as $i => $value) {
                    $query->createFilterQuery($field . '-' . $i)->setQuery($field . ':"' . $value . '"');
                }
            }
        }
    }
}