<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SolrManagerObjectStoragePass implements CompilerPassInterface
{
    /**
     * @{inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('zicht_solr.object_storage');

        foreach (['document.id_generator', 'mapping.marshaller', 'mapping.repository'] as $suffix) {
            foreach (array_keys($container->findTaggedServiceIds('zicht_solr.' . $suffix)) as $service) {
                $definition->addMethodCall('add', [new Reference($service),  $suffix]);
            }
        }
    }
}