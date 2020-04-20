<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ZichtSolrExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('admin.xml');
        $loader->load('services.xml');
        $loader->load('commands.xml');

        if (!isset($config['manager']['type']) || 'entity' !== $config['manager']['type']) {
            $container->setDefinition('zicht_solr.manager', clone $container->getDefinition('zicht_solr.manager.default_manager'));
        } else {
            $container->setDefinition('zicht_solr.manager', clone $container->getDefinition('zicht_solr.manager.entity_manager'));
        }

        $container->getDefinition('_zicht_solr.http_client')->setArguments([
            ['base_url' => sprintf('http://%s:%d%s/%s/', $config['host'], $config['port'], $config['path'], $config['core'])],
        ]);
        $container->setParameter('zicht_solr.managed', $config['managed']);
        $container->setParameter('zicht_solr.absolute_base_url', sprintf('http://%s:%d%s/', $config['host'], $config['port'], $config['path']));
    }
}
