<?php

namespace App\Form;

use App\Entity\Csw;
use App\Service\Configuration;
use App\Service\Plugin;
use App\Service\Source;
use App\Service\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CswHarvesterType extends AbstractType
{
    private $source;
    private $config;
    private $user;
    private $plugin;

    public function __construct(
        Source $source,
        Configuration $config,
        User $user,
        Plugin $plugin
    ) {
        $this->source = $source;
        $this->config = $config;
        $this->user = $user;
        $this->plugin = $plugin;
    }

    public function __destruct()
    {
        unset($this->source, $this->config, $this->plugin);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $users = [];
        foreach ($this->user->findAll() as $user) {
            $users[$user->getUsername()] = $user->getUsername();
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'required' => true,
            ])
            ->add('url', TextType::class, [
                'label' => 'URL',
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
            ->add('profileMapping', HiddenType::class)
            ->add('filter', TextareaType::class, [
                'label' => 'Filter (JSON)',
                'required' => false,
            ])
        ;

        $fields = $this->config->get('hierarchy_levels', 'plugin', 'metador_core');
        $fields = $fields ? $fields : [];

        $profiles = array_combine(
            array_keys($this->plugin->getActiveProfiles()),
            array_keys($this->plugin->getActiveProfiles())
        );

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

        $builder->get('filter')->addModelTransformer($textJsonTransformer);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($fields, $profiles) {
                /* @var Csw $data */
                $data = $event->getData();
                if (null === $data) {
                    return;
                }
                $pm = $data->getProfileMapping();
                $form = $event->getForm();
                foreach ($fields as $field) {
                    $_data = '';
                    if ($pm && isset($pm[$field])) {
                        $_data = $pm[$field];
                    }
                    $form->add($field, ChoiceType::class, [
                        'required' => false,
                        'choices' => $profiles,
                        'mapped' => false,
                        'data' => $_data,
                    ]);
                }
            }
        );

        $pmComposite = [];

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($fields, $profiles, &$pmComposite) {
                if (($data = $event->getData())) {
                    foreach ($fields as $field) {
                        if (isset($data[$field]) && $data[$field]) {
                            $pmComposite[$field] = $data[$field];
                        }
                    }
                }
            }
        );

        $builder->get('profileMapping')->addModelTransformer(new CallbackTransformer(
            function ($value) {
                return is_array($value) ? json_encode($value, true) : '[]';
            },
            function ($value) use (&$pmComposite) {
                return is_array($pmComposite) ? $pmComposite : [];
            }
        ));
    }
}
