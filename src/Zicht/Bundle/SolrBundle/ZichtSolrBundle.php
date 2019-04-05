<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass\SolrAuthorizationVoterPass;
use Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass\SolrDocumentMetadataFactoryPass;
use Zicht\Bundle\SolrBundle\DependencyInjection\CompilerPass\SolrManagerObjectStoragePass;

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
        $container->addCompilerPass(new SolrDocumentMetadataFactoryPass());
        $container->addCompilerPass(new SolrManagerObjectStoragePass());
        $container->addCompilerPass(new SolrAuthorizationVoterPass());
    }
}
