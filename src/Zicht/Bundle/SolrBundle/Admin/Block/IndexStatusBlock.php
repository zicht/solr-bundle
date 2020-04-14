<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Admin\Block;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Zicht\Bundle\SolrBundle\Manager\IndexStatusManager;

class IndexStatusBlock extends AbstractBlockService
{
    /** @var IndexStatusManager */
    private $indexStatusManager;

    /**
     * @param Environment|string $twigOrName
     * @param EngineInterface|null $templating
     * @param IndexStatusManager $indexStatusManager
     */
    public function __construct($twigOrName, EngineInterface $templating, IndexStatusManager $indexStatusManager)
    {
        parent::__construct($twigOrName, $templating);

        $this->indexStatusManager = $indexStatusManager;
    }

    /** {@inheritDoc} */
    public function configureSettings(OptionsResolver $resolver)
    {
        parent::configureSettings($resolver);
        $resolver->setDefaults([
            'template' => '@ZichtSolr/IndexStatus/block_status.html.twig',
            'translation_domain' => 'admin',
            'use_cache' => false,
        ]);
    }

    /** {@inheritDoc} */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        return $this->renderPrivateResponse($blockContext->getTemplate(), [
            'block_context' => $blockContext,
            'block' => $blockContext->getBlock(),
            // @todo: add actions
            'statuses' => [
                'needs_reload' => $this->indexStatusManager->getIndexStatus()->getNeedsReload(),
                'is_reloading' => $this->indexStatusManager->getIndexStatus()->getIsReloading(),
                'needs_reindex' => $this->indexStatusManager->getIndexStatus()->getNeedsReindex(),
                'is_reindexing' => $this->indexStatusManager->getIndexStatus()->getIsReindexing(),
            ],
        ], $response);
    }
}
