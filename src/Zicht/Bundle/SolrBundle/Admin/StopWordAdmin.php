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
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class StopWordAdmin
 */
class StopWordAdmin extends AbstractAdmin
{
    /**
     * @{inheritDoc}
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
     * @{inheritDoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('managed')
            ->add('value')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('value')
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
     * @{inheritDoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->tab('admin.tab.general')
                ->add('managed', ChoiceType::class, $this->getManagedFieldOptions())
                ->add('value')
                ->end()
            ->end()
        ;
    }

    private function getManagedFieldOptions()
    {
        return [
            'choices' => $this->getConfigurationPool()->getContainer()->getParameter('zicht_solr.managed'),
            'choice_label' => function($k, $v) { return $k; }
        ];
    }
}
