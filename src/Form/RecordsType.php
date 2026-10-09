<?php

namespace App\Form;

use App\Service\Source;
use App\Service\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class RecordsType extends AbstractType
{
    public function __construct(
        private readonly Source $source,
        private readonly User $user,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $users = [];
        foreach ($this->user->findAll() as $user) {
            $users[$user->getUsername()] = $user->getUsername();
        }

        $builder
            ->add('active', ChoiceType::class, [
                'label' => 'Aktiv',
                'required' => true,
                'choices' => [
                    'Ja'   => true,
                    'Nein' => false,
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'required' => true,
            ])
            ->add('slug', TextType::class, [
                'label' => 'URL Titel',
                'required' => true,
            ])
            ->add('source', ChoiceType::class, [
                'label' => 'Quelle',
                'required' => true,
                'choices' => $this->source->allValues(),
            ])
            ->add('username', ChoiceType::class, [
                'label' => 'Benutzer',
                'required' => true,
                'choices' => $users,
            ])
            ->add('abstract', TextareaType::class, [
                'label' => 'Beschreibung',
                'required' => false,
            ])
            ->add('keywords', TextareaType::class, [
                'label' => 'Schlüsselwörter',
                'required' => false,
            ])
            ->add('fees', TextType::class, [
                'label' => 'Gebühren',
                'required' => false,
                'empty_data' => 'none',
            ])
            ->add('accessConstraints', TextType::class, [
                'label' => 'Zugangsbeschränkungen',
                'required' => false,
                'empty_data' => 'none',
            ])
            ->add('providerName', TextType::class, [
                'label' => 'Betreiber',
                'required' => true,
            ])
            ->add('providerSite', UrlType::class, [
                'label' => 'Webseite des Betreibers',
                'required' => false,
            ])
            ->add('individualName', TextType::class, [
                'label' => 'Zuständige Person',
                'required' => false,
            ])
            ->add('positionName', TextType::class, [
                'label' => 'Position',
                'required' => false,
            ])
            ->add('phoneVoice', TextType::class, [
                'label' => 'Telefon',
                'required' => false,
            ])
            ->add('phoneFacsimile', TextType::class, [
                'label' => 'Fax',
                'required' => false,
            ])
            ->add('deliveryPoint', TextType::class, [
                'label' => 'Straße',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Ort',
                'required' => false,
            ])
            ->add('administrativeArea', TextType::class, [
                'label' => 'Bundesland',
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'PLZ',
                'required' => false,
            ])
            ->add('country', TextType::class, [
                'label' => 'Land',
                'required' => false,
            ])
            ->add('electronicMailAddress', TextType::class, [
                'label' => 'E-Mail',
                'required' => false,
            ])
            ->add('onlineResourse', TextType::class, [
                'label' => 'Onlineresourse',
                'required' => false,
            ])
            ->add('filter', TextareaType::class, [
                'label' => 'Filter (JSON)',
                'required' => false,
            ]);

        $csvArrayTransformer = new CallbackTransformer(
            static function ($textAsArray) {
                // transform the array to a string
                return isset($textAsArray) ? implode(', ', $textAsArray) : '';
            },
            static function ($textAsString) {
                // transform the string back to an array
                return isset($textAsString) ? preg_split('/\s?,\s?/', trim($textAsString)) : [];
            }
        );

        $textJsonTransformer = new CallbackTransformer(
            static function ($data) {
                if (is_array($data)) {
                    return json_encode($data, JSON_PRETTY_PRINT);
                }

                return json_encode(
                    !empty($data) ? json_decode($data, true, 512, JSON_FORCE_OBJECT) : [],
                    JSON_PRETTY_PRINT
                );
            },
            static function ($text) {
                return is_string($text) ? json_decode($text, true, 512, JSON_FORCE_OBJECT) : [];
            }
        );

        $builder->get('keywords')->addModelTransformer($csvArrayTransformer);
        $builder->get('accessConstraints')->addModelTransformer($csvArrayTransformer);
        $builder->get('filter')->addModelTransformer($textJsonTransformer);
    }
}
