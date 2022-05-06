<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ZichtSolrBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DependencyInjection\CompilerPass\AddMapperPass());
        $container->addCompilerPass(new DependencyInjection\CompilerPass\AddRepositoryPass());
    }
}
