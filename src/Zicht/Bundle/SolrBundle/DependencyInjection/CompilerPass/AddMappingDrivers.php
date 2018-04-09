<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddMappingDrivers implements CompilerPassInterface
{
    /**
     * @{inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('zicht_solr.mapper.document_metadata_factory');
        foreach ($container->getParameter('doctrine.entity_managers') as $manager) {
            /** each entity manager has 2 argument, connection and config */
            $definitionName = (string)$container->getDefinition($manager)->getArgument(1);
            foreach ($container->getDefinition($definitionName )->getMethodCalls() as list($name, $arguments)) {
                if ('setMetadataDriverImpl' === $name) {
                    $definition->addArgument($arguments[0]);
                }
            }
        }
    }
}
