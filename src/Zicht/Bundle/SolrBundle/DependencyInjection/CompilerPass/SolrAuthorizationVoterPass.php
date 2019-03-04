<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SolrAuthorizationVoterPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
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
}
