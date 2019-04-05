<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zicht\Bundle\SolrBundle\Mapping\ClassNameResolver;

/**
 * Class SolrDocumentMetadataFactoryPass
 *
 * build class name resolver class by getting all aliases from
 * doctrine so we can support proxy classes and short aliases
 * like: Foo:PageEntity
 *
 *
 * will get MappingDriver from the doctrine entity managers so
 * we can get all managed entities and use them to build a list
 * to read the annotations from.
 *
 * @package Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass
 */
class SolrDocumentMetadataFactoryPass implements CompilerPassInterface
{
    /**
     * @{inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->setClassNameResolver($container);
        $definition = $container->getDefinition('zicht_solr.mapper.document_metadata_factory');
        $definition->addArgument(new Reference('zicht_solr.mapper.class_name_resolver'));
        foreach ($container->getParameter('doctrine.entity_managers') as $manager) {
            /** each entity manager has 2 argument, connection and config */
            $definitionName = (string)$container->getDefinition($manager)->getArgument(1);
            foreach ($container->getDefinition($definitionName)->getMethodCalls() as list($name, $arguments)) {
                if ('setMetadataDriverImpl' === $name) {
                    $definition->addArgument($arguments[0]);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function setClassNameResolver(ContainerBuilder $container)
    {
        $list = [];
        foreach ($container->getParameter('doctrine.entity_managers') as $manager) {
            foreach($container->getDefinition((string)$container->getDefinition($manager)->getArgument(1))->getMethodCalls() as list($name, $values)) {
                if ('setEntityNamespaces' === $name) {
                    $list += $values;
                }
            }
        }
        $definition = new Definition(ClassNameResolver::class, $list);
        $definition->setPrivate(true);
        $container->setDefinition('zicht_solr.mapper.class_name_resolver', $definition);
    }
}
