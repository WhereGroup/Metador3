<?php

namespace App\Form;

use App\Service\Source;
use App\Service\User;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ExportType extends BasicType
{
    public function __construct(
        private readonly Source $source,
        private readonly User $user,
        private readonly string $exportFolder
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
            ->add('exportFolder', ChoiceType::class, [
                'label' => 'Export in Ordner',
                'required' => false,
                'choices' => $this->findFolders($this->exportFolder),
            ])
            ->add('removeSourceData', CheckboxType::class, ['label' => 'Quelldaten löschen'])
            ->add('removeDataOnDestination', CheckboxType::class, ['label' => 'Zieldaten überschreiben'])
            ->add('filter', TextareaType::class, [
                'label' => 'Filter (JSON)',
                'required' => false,
            ])
        ;

        $builder->get('filter')->addModelTransformer(new CallbackTransformer(
            static function ($data): string {
                if (is_array($data)) {
                    return json_encode($data, JSON_PRETTY_PRINT) ?: '{}';
                }

                return json_encode(
                    !empty($data) ? json_decode((string) $data, true, 512, JSON_FORCE_OBJECT) : [],
                    JSON_PRETTY_PRINT
                ) ?: '{}';
            },
            static function ($text): array {
                if (!is_string($text) || $text === '') {
                    return [];
                }
                $decoded = json_decode($text, true, 512, JSON_FORCE_OBJECT);

                return is_array($decoded) ? $decoded : [];
            }
        ));
    }
}
