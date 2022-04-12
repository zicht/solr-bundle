<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Zicht\Bundle\SolrBundle\Manager\SolrEntityManager;
use Zicht\Bundle\SolrBundle\Manager\SolrManager;

class ZichtSolrExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
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
            $container->removeAlias(SolrEntityManager::class);
            $container->setAlias(SolrEntityManager::class, 'zicht_solr.manager');
        }
        $container->removeAlias(SolrManager::class);
        $container->setAlias(SolrManager::class, 'zicht_solr.manager');

        $solrArguments = $container->getDefinition('zicht_solr.solr')->getArguments();
        $solrArguments[0] = $config;
        $container->getDefinition('zicht_solr.solr')->setArguments($solrArguments);

        $container->setParameter('zicht_solr.managed', $config['managed']);
    }
}
