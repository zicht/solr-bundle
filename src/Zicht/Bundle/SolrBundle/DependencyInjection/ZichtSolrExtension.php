<?php
/**
 * @author    Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\ExpressionLanguage\Expression;
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
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('cache.xml');
        $loader->load('net.xml');
        $loader->load('services.xml');
        $loader->load('commands.xml');


// array (size=5)
//  'port' => int 8983
//  'host' => string 'solr.zicht.intern' (length=17)
//  'path' => string '/solr' (length=5)
//  'core' => string 'amst001_testing' (length=15)
//  'mapper' =>
//    array (size=2)
//      'cache' => string 'zicht_solr.cache.filesystem' (length=27)
//      'naming_strategy' => string 'doctrine.orm.naming_strategy.underscore' (length=39)

        $container
            ->getDefinition('zicht_solr.mapper.document_metadata_factory')
            ->replaceArgument(0 , new Reference($config['mapper']['naming_strategy']))
            ->replaceArgument(1 , new Reference($config['mapper']['cache']));


        $definition = new Definition(Uri::class);
        $definition->setArguments([sprintf('http://%s:%d%s/%s/', $config['host'], $config['port'], $config['path'], $config['core'])]);
        $definition->setPublic(false);

        $container->setDefinition('zicht_solr.uri', $definition);
        $container->getDefinition('zicht_solr.net.handler.socket')->replaceArgument(0, new Reference('zicht_solr.uri'));

        if ($container->getParameter('kernel.debug')) {

            $socketDefinition = $container->getDefinition('zicht_solr.net.handler.socket');

            if (is_a($socketDefinition->getClass(), HandlerDebugInterface::class, true)) {

                $container->setDefinition(
                    'zicht_solr.debug.request.logger',
                    (new Definition(TempStream::class))->setPublic(false)
                );

                $socketDefinition->addMethodCall('setDebug', [new Reference('zicht_solr.debug.request.logger')]);

                $container
                    ->getDefinition('_zicht_solr.data_collector')
                    ->addMethodCall('addDebugger', ['requests', new Reference('zicht_solr.debug.request.logger')]);
            }
        }

//        if ($container->getParameter('kernel.debug'))

//        $container->getDefinition('_zicht_solr.http_client')->setArguments([
//            ['base_url' => sprintf('http://%s:%d%s/%s/', $config['host'], $config['port'], $config['path'], $config['core'])]
//        ]);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $cacheId
     */
    protected function setMetaFactoryArguments(ContainerBuilder $container, $cacheId)
    {
        $args = [new Reference($cacheId), new Reference('annotations.reader')];

        foreach ($container->getParameter('doctrine.entity_managers') as $manager) {
            /** each entity manager has 2 argument, connection and config */
            $definitionName = (string)$container->getDefinition($manager)->getArgument(1);
            foreach ($container->getDefinition($definitionName )->getMethodCalls() as list($name, $arguments)) {
                if ('setMetadataDriverImpl' === $name) {
                    $args[] = $arguments[0];
                }
            }
        }

        $container
            ->getDefinition('zicht_solr.metadata.factory')
            ->setArguments($args);

    }
}
