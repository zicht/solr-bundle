<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\IndexStatus;

interface IndexStatusInterface
{
    /**
     * @return string
     */
    public function getCore();

    /**
     * @return bool
     */
    public function getNeedsReload();

    /**
     * @param bool $needsReload
     */
    public function setNeedsReload($needsReload);

    /**
     * @return bool
     */
    public function getNeedsReindex();

    /**
     * @param bool $needsReindex
     */
    public function setNeedsReindex($needsReindex);

    /**
     * @return bool
     */
    public function getIsReloading();

    /**
     * @param bool $isReloading
     */
    public function setIsReloading($isReloading);

    /**
     * @return bool
     */
    public function getIsReindexing();

    /**
     * @param bool $isReindexing
     */
    public function setIsReindexing($isReindexing);
}