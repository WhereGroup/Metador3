<?php

namespace App\Form;

use App\Service\Configuration;
use App\Service\Plugin;
use App\Service\Source;
use App\Service\User;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ImportType extends BasicType
{
    public function __construct(
        private readonly Source $source,
        private readonly Configuration $config,
        private readonly User $user,
        private readonly Plugin $plugin,
        private readonly string $importFolder
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titel'])
            ->add('token', TextType::class, ['label' => 'Token', 'attr' => ['readonly' => true]])
            ->add('source', ChoiceType::class, [
                'label' => 'Datenquelle',
                'required' => true,
                'choices' => $this->source->allValues(),
            ])
            ->add('username', ChoiceType::class, [
                'label' => 'Benutzer',
                'required' => true,
                'choices' => $this->getUsers($this->user),
            ])
            ->add('importFolder', ChoiceType::class, [
                'label' => 'Import aus Ordner',
                'required' => false,
                'choices' => $this->findFolders($this->importFolder),
            ])
            ->add('removeSourceData', CheckboxType::class, ['label' => 'Quelldaten löschen'])
            ->add('removeDataOnDestination', CheckboxType::class, ['label' => 'Zieldaten überschreiben'])
            ->add('profileMapping', HiddenType::class)
        ;

        $fields = $this->getFields($this->config);
        $profiles = $this->getProfiles($this->plugin);

        $this->addEventListeners($builder, $fields, $profiles);
        $this->addProfileMapping($builder, $fields, $profiles);
    }
}
