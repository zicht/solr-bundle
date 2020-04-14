<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\IndexStatus;

use Zicht\Bundle\SolrBundle\Manager\IndexStatusManager;

class IndexStatusProxy implements IndexStatusInterface
{
    /** @var IndexStatusManager */
    private $indexStatusManager;

    /**
     * @param IndexStatusManager $indexStatusManager
     */
    public function __construct(IndexStatusManager $indexStatusManager)
    {
        $this->indexStatusManager = $indexStatusManager;
    }

    /**
     * Pass down setFoo and getBar calls to the IndexStatusManager as setIndexStatusFoo and getIndexStatusBar calls,
     * which in turn makes the calls end up on the actual IndexStatus object
     *
     * @param string $name
     * @param mixed[] $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (strlen($name) > 3 && (0 === strpos($name, 'set') || 0 === strpos($name, 'get'))) {
            $name = substr($name, 0, 3) . 'IndexStatus' . substr($name, 3);
            return \call_user_func_array([$this->indexStatusManager, $name], $arguments);
        }

        throw new \BadMethodCallException('Cannot pass down non-accessor method calls to the IndexStatus');
    }

    /** {@inheritDoc} */
    public function getCore()
    {
        return $this->__call(__FUNCTION__, []);
    }

    /** {@inheritDoc} */
    public function getNeedsReload()
    {
        return $this->__call(__FUNCTION__, []);
    }

    /** {@inheritDoc} */
    public function setNeedsReload($needsReload)
    {
        $this->__call(__FUNCTION__, [$needsReload]);
    }

    /** {@inheritDoc} */
    public function getNeedsReindex()
    {
        return $this->__call(__FUNCTION__, []);
    }

    /** {@inheritDoc} */
    public function setNeedsReindex($needsReindex)
    {
        $this->__call(__FUNCTION__, [$needsReindex]);
    }

    /** {@inheritDoc} */
    public function getIsReloading()
    {
        return $this->__call(__FUNCTION__, []);
    }

    /** {@inheritDoc} */
    public function setIsReloading($isReloading)
    {
        $this->__call(__FUNCTION__, [$isReloading]);
    }

    /** {@inheritDoc} */
    public function getIsReindexing()
    {
        return $this->__call(__FUNCTION__, []);
    }

    /** {@inheritDoc} */
    public function setIsReindexing($isReindexing)
    {
        $this->__call(__FUNCTION__, [$isReindexing]);
    }
}
