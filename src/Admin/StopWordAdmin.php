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
use Zicht\Bundle\SolrBundle\Entity\StopWord;

/**
 * @extends AbstractAdmin<StopWord>
 */
class StopWordAdmin extends AbstractAdmin
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
            'delete',
        ]);
        parent::configureRoutes($collection);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('managed')
            ->add('value', null, ['label' => 'filter.label_stop_word']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
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
            );
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('admin.tab.general')
            ->add('managed', ChoiceType::class, $this->getManagedFieldOptions())
            ->add('value', null, ['label' => 'form.label_stop_word'])
            ->end()
            ->end();
    }

    private function getManagedFieldOptions(): array
    {
        return [
            'choices' => $this->managed,
            'choice_label' => fn ($k, $v) => 'choice.managed_stop_words.' . $k,
            'choice_translation_domain' => 'admin',
        ];
    }
}
