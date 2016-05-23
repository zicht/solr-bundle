<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle;

use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\HttpKernel\Bundle\Bundle;
use \Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass\AddMapperPass;

class ZichtSolrBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddMapperPass());
    }
}