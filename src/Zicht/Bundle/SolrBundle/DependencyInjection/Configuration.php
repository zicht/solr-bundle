<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('zicht_solr');

        $rootNode
            ->children()
                ->arrayNode('mapper')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('cache')
                            ->info("This should be a service id of a class that implements 'Psr\SimpleCache\CacheInterface'")
                            ->defaultValue('zicht_solr.cache.filesystem')
                        ->end()
                        ->scalarNode('naming_strategy')
                            ->info('This should be a service id of a class that implements "Doctrine\ORM\Mapping\NamingStrategy" and will be used for generating the solr field then no name is provided')
                            ->defaultValue('doctrine.orm.naming_strategy.underscore')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('port')->end()
                ->scalarNode('host')->end()
                ->scalarNode('path')->defaultValue('/solr')->end()
                ->scalarNode('core')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
