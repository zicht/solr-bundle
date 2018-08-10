<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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
                // BC configuration
                ->scalarNode('scheme')->defaultValue('http')->end()
                ->scalarNode('port')->defaultValue(8983)->end()
                ->scalarNode('host')->end()
                ->scalarNode('path')->defaultValue('/solr')->end()
                ->scalarNode('core')->end()
                // new configuration
                ->scalarNode('uri')->defaultNull()->end()

            ->end()

            ->validate()
                ->always(
                    function ($v) {
                    if (is_null($v['uri'])) {
                        foreach (['host', 'core'] as $required) {
                            if (empty($v[$required])) {
                                throw new InvalidConfigurationException(sprintf('The child node "%s" at path "zicht_solr" must be configured.', $required));
                            }
                        }
                        if (!in_array($v['scheme'], ['http', 'https'])) {
                            throw new InvalidConfigurationException('Unsupported scheme provided for child node "host" at path "zicht_solr", got "%s" while expected "http" or "https"', $v['scheme']);
                        }
                        $v['uri'] = sprintf('%s://%s:%d%s/%s/', $v['scheme'], $v['host'], $v['port'], $v['path'], $v['core']);
                    }
                    unset($v['scheme'], $v['host'], $v['port'], $v['path'], $v['core']);
                    return $v;
                    }
                )
            ->end();
        ;

        return $treeBuilder;
    }
}
