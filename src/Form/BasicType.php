<?php

namespace App\Form;

use App\Entity\ImportExport;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BasicType extends AbstractType
{
    public function findFolders(string $path): array
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if (!$fs->exists($path)) {
            $fs->mkdir($path, 0755);
        }

        $folders = [];
        $finder->in($path)->directories()->ignoreUnreadableDirs();

        foreach ($finder as $folder) {
            $name = $folder->getFilename();
            if ($name === '') {
                continue;
            }

            $folders[$name] = $name;
        }

        return $folders;
    }

    public function getFields($configService): array
    {
        $fields = $configService->get('hierarchy_levels', 'plugin', 'metador_core');

        return is_array($fields) ? $fields : [];
    }

    public function getProfiles($pluginService): array
    {
        $keys = array_keys($pluginService->getActiveProfiles());

        return array_combine($keys, $keys);
    }

    public function getUsers($userService): array
    {
        $users = [];
        foreach ($userService->findAll() as $user) {
            $users[$user->getUsername()] = $user->getUsername();
        }

        return $users;
    }

    public function addEventListeners(FormBuilderInterface $builder, array $fields, array $profiles): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($fields, $profiles): void {
                /** @var ImportExport|null $data */
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
    }

    public function addProfileMapping(FormBuilderInterface $builder, array $fields, array $profiles): void
    {
        $pmComposite = [];

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($fields, &$pmComposite): void {
                $data = $event->getData();
                if (!is_array($data)) {
                    return;
                }
                foreach ($fields as $field) {
                    if (!empty($data[$field])) {
                        $pmComposite[$field] = $data[$field];
                    }
                }
            }
        );

        $builder->get('profileMapping')->addModelTransformer(
            new CallbackTransformer(
                static function ($value): string {
                    return is_array($value) ? (json_encode($value) ?: '[]') : '[]';
                },
                static function ($value) use (&$pmComposite): array {
                    return is_array($pmComposite) ? $pmComposite : [];
                }
            )
        );
    }
}
