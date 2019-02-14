<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\EventListener;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Zicht\Bundle\SolrBundle\Mapping\DocumentMapperMetadataFactory;

/**
 * Class CacheWarmerListener
 * @package Zicht\Bundle\SolrBundle\EventListener
 */
class CacheWarmerListener implements CacheWarmerInterface
{
    /** @var DocumentMapperMetadataFactory  */
    private $factory;

    /**
     * LoadClassMetadataListener constructor.
     * @param DocumentMapperMetadataFactory $factory
     */
    public function __construct(DocumentMapperMetadataFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @{inheritDoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * @{inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        // load all entities mapping cache so caching is build
        foreach ($this->factory->getEntities() as $entity) {
            $this->factory->getDocumentMapperMetadataForClass($entity);
        }
    }
}
