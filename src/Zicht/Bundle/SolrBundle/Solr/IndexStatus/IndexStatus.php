<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\IndexStatus;

class IndexStatus implements IndexStatusInterface
{
    /** @var string */
    private $core;

    /** @var bool */
    private $needsReload = false;

    /** @var bool */
    private $needsReindex = false;

    /** @var bool */
    private $isReloading = false;

    /** @var bool */
    private $isReindexing = false;

    /**
     * @param string $core The name of the SOLR core this status applies to
     */
    public function __construct($core)
    {
        $this->core = $core;
    }

    /**
     * @param array<string, bool|string> $state
     * @return IndexStatus
     */
    public static function fromArray($state)
    {
        if (!is_array($state) || !array_key_exists('core', $state) || !is_string($state['core'])) {
            throw new \UnexpectedValueException('Could not initialise IndexState object from given state');
        }

        $indexStatus = new self($state['core']);
        $indexStatus->setNeedsReload($state['needs_reload']);
        $indexStatus->setNeedsReindex($state['needs_reindex']);
        $indexStatus->setIsReloading($state['is_reloading']);
        $indexStatus->setIsReindexing($state['is_reindexing']);

        return $indexStatus;
    }

    /**
     * @return array<string, bool|string>
     */
    public function toArray()
    {
        return [
            'core' => $this->getCore(),
            'needs_reload' => $this->getNeedsReload(),
            'needs_reindex' => $this->getNeedsReindex(),
            'is_reloading' => $this->getIsReloading(),
            'is_reindexing' => $this->getIsReindexing(),
        ];
    }

    /**
     * @return string
     */
    public function getCore()
    {
        return $this->core;
    }

    /**
     * @return bool
     */
    public function getNeedsReload()
    {
        return $this->needsReload;
    }

    /**
     * @param bool $needsReload
     */
    public function setNeedsReload($needsReload)
    {
        $this->needsReload = $needsReload;
    }

    /**
     * @return bool
     */
    public function getNeedsReindex()
    {
        return $this->needsReindex;
    }

    /**
     * @param bool $needsReindex
     */
    public function setNeedsReindex($needsReindex)
    {
        $this->needsReindex = $needsReindex;
    }

    /**
     * @return bool
     */
    public function getIsReloading()
    {
        return $this->isReloading;
    }

    /**
     * @param bool $isReloading
     */
    public function setIsReloading($isReloading)
    {
        if (true === $isReloading) {
            $this->setNeedsReload(false);
        }
        $this->isReloading = $isReloading;
    }

    /**
     * @return bool
     */
    public function getIsReindexing()
    {
        return $this->isReindexing;
    }

    /**
     * @param bool $isReindexing
     */
    public function setIsReindexing($isReindexing)
    {
        if (true === $isReindexing) {
            $this->setNeedsReindex(false);
        }
        $this->isReindexing = $isReindexing;
    }
}
