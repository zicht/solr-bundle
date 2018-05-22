<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ZichtSolrBundle
 */
class ZichtSolrBundle extends Bundle
{
    /**
     * @{inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

//        $container->addCompilerPass(new DependencyInjection\CompilerPass\AddMapperPass());
        $container->addCompilerPass(new DependencyInjection\CompilerPass\AddMappingDriversPass());
        $container->addCompilerPass(new DependencyInjection\CompilerPass\AddRepositoryPass());
        $container->addCompilerPass(new DependencyInjection\CompilerPass\SolrManagerObjectStoragePass());
    }
}