<?php
/**
 * @author    Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection;

use Symfony\Component\Cache\Simple\ApcuCache;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\PhpFilesCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Zicht\Http\Handler\HandlerDebugInterface;
use Zicht\Http\Message\Uri;
use Zicht\Http\Stream\TempStream;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
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
        $loader->load('net.xml');
        $loader->load('services.xml');
        $loader->load('commands.xml');

        $container->setDefinition('zicht_solr.uri', (new Definition(Uri::class, [$config['uri']]))->setPublic(false));
        $container->getDefinition('zicht_solr.http.handler.socket')->replaceArgument(0, new Reference('zicht_solr.uri'));

        switch ($config['mapper']['cache']['type']) {
            case 'service':
                $cache = new Reference($config['mapper']['cache']['name']);
                break;
            case 'auto':
                switch ($config['mapper']['cache']['name']) {
                    case 'file':
                        $definition = new Definition(PhpFilesCache::class, ['solr', 0, '%kernel.cache_dir%']);
                        break;
                    case 'array':
                        $definition = new Definition(ArrayCache::class, [0, false]);
                        break;
                    case 'apcu':
                        $definition = new Definition(ApcuCache::class, ['solr']);
                        break;
                }
                if (isset($definition)) {
                    $container->setDefinition('zicht_solr.cache.default', $definition)->setPublic(false);
                    $cache = new Reference('zicht_solr.cache.default');
                }
                break;
        }

        if (!isset($cache)) {
            throw new \RuntimeException(sprintf('Failed to set cache for zicht_solr.mapper.document_metadata_factory with %s', var_export($config['mapper']['cache'], true)));
        }

        $container
            ->getDefinition('zicht_solr.mapper.document_metadata_factory')
            ->replaceArgument(0, new Reference($config['mapper']['naming_strategy']))
            ->replaceArgument(1, $cache);

        if ($container->getParameter('kernel.debug')) {
            $socketDefinition = $container->getDefinition('zicht_solr.http.handler.socket');
            if (is_a($socketDefinition->getClass(), HandlerDebugInterface::class, true)) {
                $container->setDefinition('zicht_solr.debug.request.logger',  (new Definition(TempStream::class))->setPublic(false));
                $container->getDefinition('zicht_solr.data_collector')->addMethodCall('addDebugger', ['requests', new Reference('zicht_solr.debug.request.logger')]);
                $socketDefinition->addMethodCall('setDebug', [new Reference('zicht_solr.debug.request.logger')]);
            }
        }
    }
}
