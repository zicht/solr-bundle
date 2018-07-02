<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zicht\Bundle\SolrBundle\Solr\SolrManager;

/**
 * Class SolrManagerObjectStoragePass
 *
 * get all tagged services and add them to the solr manager object
 * storage so we can use serviced from the service container without
 * a direct dependency on the container.
 *
 * Available tags are:
 *
 * zicht_solr.document.id_generator     used for injecting id generators instances (with dependencies) that are used for
 *                                      generating document id`s (see @Zicht\Bundle\SolrBundle\Mapping\IdGenerator)
 * zicht_solr.mapping.marshaller        used for injecting marshaller instances (with dependencies) that are used for
 *                                      marshalling mapped data (see @Zicht\Bundle\SolrBundle\Mapping\Marshaller)
 * zicht_solr.mapping.repository        used for injecting repository instances (with dependencies) used for the
 *                                      @Zicht\Bundle\SolrBundle\Mapping\Document::repository
 *
 * @package Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass
 */

class SolrManagerObjectStoragePass implements CompilerPassInterface
{
    /**
     * @return array|string[]
     */
    protected function getScopes()
    {
        return [
            SolrManager::SCOPE_DOCUMENT_ID_GENERATOR,
            SolrManager::SCOPE_MAPPING_MARSHALLER,
            SolrManager::SCOPE_MAPPING_REPOSITORY
        ];
    }

    /**
     * @{inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('zicht_solr.object_storage');

        foreach ($this->getScopes() as $name) {
            foreach (array_keys($container->findTaggedServiceIds('zicht_solr.' . $name)) as $service) {
                $definition->addMethodCall('add', [new Reference($service),  $name]);
            }
        }
    }
}
