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
                        ->arrayNode('cache')
                            ->defaultValue('file')
                            ->info('This should be a service prefixed with @ or on of "file", "array", or "apcu" values.')
                            ->beforeNormalization()
                            ->ifString()
                                ->then(function($value) {
                                    if ($value[0] === '@') {
                                        return [
                                            'type' => 'service',
                                            'name' => substr($value, 1),
                                        ];
                                    } else {
                                        return [
                                            'type' => 'auto',
                                            'name' => $value,
                                        ];
                                    }
                                })
                            ->end()
                            ->children()
                                ->enumNode('type')
                                    ->values(['service', 'auto'])
                                ->end()
                                ->scalarNode('name')
                                    ->isRequired()
                                ->end()
                            ->end()
                            ->validate()
                                ->always(function($v) {
                                    if ('auto' === $v['type'] && !in_array($v['name'], ['file', 'array', 'apcu'])) {
                                        throw new \RuntimeException('Invalid cache value,expected one of "file", "array" or "apcu" got ' . $v['name']);
                                    }
                                })
                            ->end()
                        ->end()
                        ->scalarNode('naming_strategy')
                            ->info('This should be a service id of a class that implements "Doctrine\ORM\Mapping\NamingStrategy" and will be used for generating the solr field then no name is provided')
                            ->defaultValue('doctrine.orm.naming_strategy.underscore')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('uri')->defaultNull()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
