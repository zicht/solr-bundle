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

        if (null === $cache = $this->getCacheReference($config, $container)) {
            throw new \RuntimeException(sprintf('Failed to set cache for zicht_solr.mapper.document_metadata_factory with %s', var_export($config['mapper']['cache'], true)));
        }

        $container
            ->getDefinition('zicht_solr.mapper.document_metadata_factory')
            ->replaceArgument(0, new Reference($config['mapper']['naming_strategy']))
            ->replaceArgument(1, $cache);


        $this->setDebugging($container);
        $this->setVoters($container);

    }

    /**
     * @param ContainerBuilder $container
     */
    public function setVoters(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('zicht_solr.authorization.decision_manager');
        foreach ($container->findTaggedServiceIds('zicht_solr.authorization.voter') as $id => $tags) {
            $args = [new Reference($id)];
            if (isset($tags[0]['priority'])) {
                $args[] = $tags[0]['priority'];
            }
            $definition->addMethodCall('addVoter', $args);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function setDebugging(ContainerBuilder $container)
    {
        if ($container->getParameter('kernel.debug')) {
            $definition = $container->getDefinition('zicht_solr.http.handler.socket');
            if (is_a($definition ->getClass(), HandlerDebugInterface::class, true)) {
                $container->setDefinition('zicht_solr.debug.request.logger',  (new Definition(TempStream::class))->setPublic(false));
                $container->getDefinition('zicht_solr.data_collector')->addMethodCall('addDebugger', ['requests', new Reference('zicht_solr.debug.request.logger')]);
                $definition->addMethodCall('setDebug', [new Reference('zicht_solr.debug.request.logger')]);
            }
        }
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @return null|Reference
     */
    private function getCacheReference(array $config, ContainerBuilder $container)
    {
        switch ($config['mapper']['cache']['type']) {
            case 'service':
                return new Reference($config['mapper']['cache']['name']);
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
                    default:
                        throw new \InvalidArgumentException('invalid cache type ' . $config['mapper']['cache']['name'] . ' for type auto');
                }
                $container->setDefinition('zicht_solr.cache.default', $definition)->setPublic(false);
                return new Reference('zicht_solr.cache.default');
                break;
        }
        return null;
    }
}
