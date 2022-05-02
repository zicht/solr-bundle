<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Zicht\Bundle\SolrBundle\Entity\Synonym;

/**
 * @extends AbstractAdmin<Synonym>
 */
class SynonymAdmin extends AbstractAdmin
{
    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->clearExcept([
            'create',
            'list',
            'edit',
            'delete'
        ]);
        parent::configureRoutes($collection);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('managed')
            ->add('identifier')
            ->add('value', null, ['label' => 'filter.label_synonyms'])
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('identifier')
            ->add(
                'value',
                null,
                [
                    'label' => 'list.label_synonyms',
                    'template' => '@ZichtSolr/CRUD/list_multiline-multivalue.html.twig',
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

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('admin.tab.general')
                ->add('managed', ChoiceType::class, $this->getManagedFieldOptions())
                ->add('identifier')
                ->add('value', TextareaType::class, ['help' => $this->trans('help.synonyms', [], 'admin')])
                ->end()
            ->end()
        ;
    }

    private function getManagedFieldOptions(): array
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

    private function cleanUpSynonymValue(Synonym $synonym): void
    {
        if ($synonym->getValue()) {
            $cleanValues = array_filter(array_map('trim', explode("\n", $synonym->getValue())));
            $synonym->setValue(implode("\n", $cleanValues));
        }
    }
}
