<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Zicht\Bundle\SolrBundle\Entity\Synonym;
use Zicht\Bundle\SolrBundle\Manager\IndexStatusManager;

/**
 * Class SynonymAdmin
 */
class SynonymAdmin extends Admin
{
    /** @var IndexStatusManager */
    private $indexStatusManager;

    /**
     * @param IndexStatusManager $indexStatusManager
     */
    public function setIndexStatusManager(IndexStatusManager $indexStatusManager)
    {
        $this->indexStatusManager = $indexStatusManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept([
            'create',
            'list',
            'edit',
            'delete'
        ]);
        parent::configureRoutes($collection);
    }

    /**
     * {@inheritDoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('managed')
            ->add('identifier')
            ->add('value', null, ['label' => 'filter.label_synonyms'])
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('identifier')
            ->add(
                'value',
                null,
                [
                    'label' => 'list.label_synonyms',
                    'template' => 'ZichtSolrBundle:CRUD:list_multiline-multivalue.html.twig',
                ]
            )
            ->add('managed')
            ->add(
                '_action',
                'actions',
                [
                    'actions' => [
                        'edit' => [],
                        'delete' => [],
                    ],
                ]
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->tab('admin.tab.general')
                ->add('managed', 'choice', $this->getManagedFieldOptions())
                ->add('identifier')
                ->add('value', 'textarea')
                ->end()
            ->end()
        ;

        $formMapper->setHelps([
            'value' => $this->trans('help.synonyms', [], 'admin')
        ]);
    }

    /**
     * @return array
     */
    private function getManagedFieldOptions()
    {
        return [
            'choices' => $this->getConfigurationPool()->getContainer()->getParameter('zicht_solr.managed'),
            'choice_label' => function($k, $v) {
                return 'choice.managed_synonyms.' . $k;
            },
            'choice_translation_domain' => 'admin',
        ];
    }

    /**
     * @param Synonym $synonym
     */
    public function prePersist($synonym)
    {
        $this->cleanUpSynonymValue($synonym);
        parent::prePersist($synonym);
    }

    /**
     * @param Synonym $synonym
     */
    public function preUpdate($synonym)
    {
        $this->cleanUpSynonymValue($synonym);
        parent::prePersist($synonym);
    }

    private function cleanUpSynonymValue(Synonym $synonym)
    {
        if ($synonym->getValue()) {
            $cleanValues = array_filter(array_map('trim', explode("\n", $synonym->getValue())));
            $synonym->setValue(implode("\n", $cleanValues));
        }
    }

    /**
     * @param Synonym $synonym
     */
    public function postPersist($synonym)
    {
        parent::postPersist($synonym);
        $this->detectNeedReindexOrReload();
    }

    /**
     * @param Synonym $synonym
     */
    public function postUpdate($synonym)
    {
        parent::postUpdate($synonym);
        $this->detectNeedReindexOrReload();
    }

    /**
     * @param Synonym $synonym
     */
    public function postRemove($synonym)
    {
        parent::postRemove($synonym);
        $this->detectNeedReindexOrReload();
    }

    private function detectNeedReindexOrReload()
    {
        $message = '';
        if ($this->indexStatusManager->getIndexStatus()->getNeedsReload()) {
            $message = 'message_needs_reload';
        }
        if ($this->indexStatusManager->getIndexStatus()->getNeedsReindex()) {
            $message .= ($message === '' ? 'message_needs_reindex' : '_and_reindex');
        }
// @todo: Add references to the status page where reloading and reindexing can be initiated/followed
// @todo: Should variations be added for no/manual/automatic index status management?
// @todo: Should message be added for currently running reload/reindex processes (no 'need', but already pending = getIsReloading / getIsReindexing)?
        if ($message !== '') {
            $this->getRequest()->getSession()->getFlashBag()->add(
                'warning',
                $this->trans('', [], 'admin')
            );
        }
    }
}
