<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Zicht\Bundle\SolrBundle\Entity\StopWord;

/**
 * @extends AbstractAdmin<StopWord>
 */
class StopWordAdmin extends AbstractAdmin
{
    /**
     * {@inheritDoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept([
            'create',
            'list',
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
            ->add('value', null, ['label' => 'filter.label_stop_word'])
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('value', null, ['label' => 'list.label_stop_word'])
            ->add('managed')
            ->add(
                '_action',
                'actions',
                [
                    'actions' => [
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
                ->add('managed', ChoiceType::class, $this->getManagedFieldOptions())
                ->add('value', null, ['label' => 'form.label_stop_word'])
                ->end()
            ->end()
        ;
    }

    /**
     * @return array
     */
    private function getManagedFieldOptions()
    {
        return [
            'choices' => $this->getConfigurationPool()->getContainer()->getParameter('zicht_solr.managed'),
            'choice_label' => function($k, $v) {
                return 'choice.managed_stop_words.' . $k;
            },
            'choice_translation_domain' => 'admin',
        ];
    }
}
