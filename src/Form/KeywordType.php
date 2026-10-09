<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KeywordType extends AbstractType
{
    public function __construct(
        private readonly ?object $plugin = null
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $profiles = [];
        if ($this->plugin !== null) {
            $help = $this->plugin->getActiveProfiles();
            $profiles = array_combine(array_keys($help), array_keys($help));
        }

        $builder
            ->add('identifier', TextType::class, ['label' => 'Identifier'])
            ->add('title', TextType::class, ['label' => 'Titel'])
            ->add('date', TextType::class, ['label' => 'Datum'])
            ->add('dateType', ChoiceType::class, [
                'label'   => 'Beschreibung',
                'choices' => [
                    'Erstellungsdatum' => 'creation',
                    'Überarbeitung'    => 'revision',
                    'Veröffentlichung' => 'publication',
                ],
            ])
            ->add('cardinality', ChoiceType::class, [
                'label'   => 'Kardinalität',
                'choices' => [
                    'Optional'    => 'optional',
                    'Conditional' => 'conditional',
                    'Mandatory'   => 'mandatory',
                ],
            ])
            ->add('keywords', TextareaType::class, [
                'label'    => 'Schlüsselwörter',
                'required' => false,
            ])
            ->add('repository', CheckboxType::class, [
                'label'    => 'Repository',
                'required' => false,
            ])
            ->add('profiles', ChoiceType::class, [
                'required' => false,
                'choices'  => $profiles,
                'multiple' => true,
                'expanded' => true,
            ]);

        $builder->get('keywords')
            ->addModelTransformer(new CallbackTransformer(
                fn ($keywords) => is_array($keywords) ? implode(', ', $keywords) : '',
                function ($keywords) {
                    $keywords = explode(',', $keywords);
                    return array_map('trim', $keywords);
                }
            ));

        $builder->get('date')
            ->addModelTransformer(new CallbackTransformer(
                fn ($date) => ($date instanceof \DateTime) ? $date->format('Y-m-d') : '',
                fn ($date) => \DateTime::createFromFormat('Y-m-d', $date)
            ));

        $builder->get('profiles')
            ->addModelTransformer(new CallbackTransformer(
                fn ($profiles) => $profiles ? json_decode($profiles, true) : [],
                fn ($profiles) => json_encode($profiles)
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
