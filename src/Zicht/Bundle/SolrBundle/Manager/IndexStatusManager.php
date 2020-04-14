<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Manager;

use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\FileResource;
use Zicht\Bundle\SolrBundle\Solr\IndexStatus\IndexStatus;
use Zicht\Bundle\SolrBundle\Solr\IndexStatus\IndexStatusInterface;
use Zicht\Bundle\SolrBundle\Solr\IndexStatus\IndexStatusProxy;

/**
 * The IndexStatusManager holds and manages the IndexStatus object and retrieves it form and persists it to the cache
 * @todo: move retrieving from and persisting it to the cache to a (cache) persister
 * @todo: move this class into sub-namespace \Zicht\Bundle\SolrBundle\Manager\IndexStatus\IndexStatusManager (to organise more IndexStatus related services)
 */
class IndexStatusManager
{
    /** @var string */
    private $core;

    /** @var IndexStatus */
    private $indexStatus;

    /** @var string */
    private $cacheFile;

    /** @var ConfigCacheInterface */
    private $cache;

    /** @var bool */
    private $debug;

    /**
     * @param string $core
     * @param string $cachePath
     * @param bool $debug
     */
    public function __construct($core, $cachePath, $debug)
    {
        $this->core = $core;
        $this->cacheFile = rtrim($cachePath, '/') . '/index_status_' . md5($this->core);
        $this->debug = $debug;
    }

    /**
     * @return ConfigCacheInterface
     */
    private function getCache()
    {
        if (!isset($this->cache)) {
            $cacheFactory = new ConfigCacheFactory($this->debug);
            $this->cache = $cacheFactory->cache(
                $this->cacheFile,
                function (ConfigCacheInterface $cache) {
                    $this->dumpIndexStatus(new IndexStatus($this->core), $cache);
                }
            );
        }

        return $this->cache;
    }

    /**
     * This method should always be called in this IndexStatusManager before using $this->>indexStatus
     * to ensure that it has been instantiated and populated with the state from the cache
     */
    private function ensureHasIndexStatus()
    {
        if (!isset($this->indexStatus)) {
            $this->indexStatus = IndexStatus::fromArray(
                include $this->getCache()->getPath()
            );
        }
    }

    /**
     * Returns the IndexStatus. If there is none, it will instantiate one from the cache. This actually always returns an
     * IndexStatusProxy to keep this IndexStatusManager in the loop when the data changes.
     *
     * @see IndexStatusProxy::__call()
     * @return IndexStatusInterface
     */
    public function getIndexStatus()
    {
        static $indexStatusProxy;

        if (!isset($indexStatusProxy)) {
            $indexStatusProxy = new IndexStatusProxy($this);
        }

        return $indexStatusProxy;
    }

    /**
     * Pass down setIndexStatusFoo and getIndexStatusBar calls onto the IndexStatus object
     * In case of a "set" call, also persist the IndexStatus object into the cache right after
     * @todo: move this to an IndexStatus persister service so that this is encapsulated and the get/setIndexStatusFoo() methods are
     *        not callable directly onto this IndexManager (but should be called via ->getIndexStatus()->get/setFoo())
     *
     * @param string $name
     * @param mixed[] $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $len = strlen('...IndexStatus');
        if (strlen($name) > $len && (0 === strpos($name, 'setIndexStatus') || 0 === strpos($name, 'getIndexStatus'))) {
            $name = substr($name, 0, 3) . substr($name, $len);
            $this->ensureHasIndexStatus();
            $result = \call_user_func_array([$this->indexStatus, $name], $arguments);

            if (0 === strpos($name, 'set')) {
                $this->persistIndexStatusToCache();
            }

            return $result;
        }

        throw new \BadMethodCallException('Cannot pass down method call to the IndexStatus');
    }

    private function persistIndexStatusToCache()
    {
        $this->ensureHasIndexStatus();
        $this->dumpIndexStatus($this->indexStatus, $this->getCache());
    }

    /**
     * @param IndexStatus $indexStatus
     * @param ConfigCacheInterface $cache
     */
    private function dumpIndexStatus(IndexStatus $indexStatus, ConfigCacheInterface $cache)
    {
        // Keep track of the IndexStatus class file and this file. If they change, then the cache needs to be invalidated
        $resources = [
            new FileResource((new \ReflectionObject($indexStatus))->getFileName()),
            new FileResource(__FILE__),
        ];

        $content = sprintf("<?php\nreturn %s;\n", var_export($indexStatus->toArray(), true));

        $cache->write($content, $resources);
    }
}
