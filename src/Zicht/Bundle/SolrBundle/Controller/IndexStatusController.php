<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Zicht\Bundle\SolrBundle\Solr\IndexStatus\IndexStatus;

class IndexStatusController extends Controller
{
    /**
     * @Route("index_status")
     * @Template
     */
    public function statusAction()
    {
        $adminPool = $this->get('sonata.admin.pool');
        /** @var IndexStatus $indexStatus */
        $indexStatus = $this->get('zicht_solr.manager.index_status_manager')->getIndexStatus();
        $indexStatus->setNeedsReload(true);

        return [
            'status' => $indexStatus,
            'admin_pool' => $adminPool,
            'base_template' => $adminPool->getTemplate('layout'),
        ];
    }
}

