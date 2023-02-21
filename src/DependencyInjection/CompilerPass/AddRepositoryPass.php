<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all tagged services
 */
class AddRepositoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('zicht_solr.manager');
        foreach ($container->findTaggedServiceIds('zicht_solr.repository') as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addRepository', [$attributes['class'], new Reference($id)]);
            }
        }
    }
}
