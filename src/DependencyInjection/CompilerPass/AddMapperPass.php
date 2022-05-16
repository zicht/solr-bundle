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
class AddMapperPass implements CompilerPassInterface
{
    public const MAPPER_TAG = 'zicht_solr.mapper';

    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('zicht_solr.manager');
        foreach (array_keys($container->findTaggedServiceIds(self::MAPPER_TAG)) as $id) {
            $definition->addMethodCall('addMapper', array(new Reference($id)));
        }
    }
}