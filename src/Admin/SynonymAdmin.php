<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Zicht\Bundle\SolrBundle\Entity\Synonym;

/**
 * @extends AbstractAdmin<Synonym>
 */
class SynonymAdmin extends AbstractAdmin
{
    private array $managed = [];

    public function setManaged(array $managed)
    {
        $this->managed = $managed;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->clearExcept([
            'create',
            'list',
            'edit',
            'delete',
        ]);
        parent::configureRoutes($collection);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('managed')
            ->add('identifier')
            ->add('value', null, ['label' => 'filter.label_synonyms']);
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
            );
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('admin.tab.general')
                ->add('managed', ChoiceType::class, $this->getManagedFieldOptions())
                ->add('identifier')
                ->add('value', TextareaType::class, ['help' => 'help.synonyms'])
                ->end()
            ->end();
    }

    private function getManagedFieldOptions(): array
    {
        return [
            'choices' => $this->managed,
            'choice_label' => fn ($k, $v) => 'choice.managed_synonyms.' . $k,
            'choice_translation_domain' => 'admin',
        ];
    }

    /**
     * @param Synonym $object
     */
    public function prePersist(object $object): void
    {
        $this->cleanUpSynonymValue($object);
        parent::prePersist($object);
    }

    /**
     * @param Synonym $object
     */
    public function preUpdate(object $object): void
    {
        $this->cleanUpSynonymValue($object);
        parent::preUpdate($object);
    }

    private function cleanUpSynonymValue(Synonym $synonym): void
    {
        if ($synonym->getValue()) {
            $cleanValues = array_filter(array_map('trim', explode("\n", $synonym->getValue())));
            $synonym->setValue(implode("\n", $cleanValues));
        }
    }
}
