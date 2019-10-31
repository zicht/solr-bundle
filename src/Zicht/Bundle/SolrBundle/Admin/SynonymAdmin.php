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
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Class SynonymAdmin
 */
class SynonymAdmin extends Admin
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
            ->add('value', null, ['label' => 'list.label_synonyms'])
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
}
