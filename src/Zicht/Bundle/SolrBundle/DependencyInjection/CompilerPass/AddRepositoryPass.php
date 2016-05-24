<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
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
    /**
     * @{inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('zicht_solr.manager');
        foreach ($container->findTaggedServiceIds('zicht_solr.repository') as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addRepository', array($attributes['class'], new Reference($id)));
            }
        }
    }
}