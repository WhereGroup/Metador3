<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add(
                'password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => 'Die Passwörten stimmen nicht überein!',
                    'required' => false,
                    'first_options'  => ['label' => 'Passwort'],
                    'second_options' => ['label' => 'Password wiederholen']
                ]
            )
            ->add('email', null, ['required' => false])
            ->add(
                'groups',
                EntityType::class,
                [
                    'class'        =>  Group::class,
                    'multiple'     => true,
                    'choice_label' => 'role',
                    'label'        => 'Gruppe',
                    'required'     => false
                ]
            )
            ->add(
                'picture',
                FileType::class,
                [
                    'label'=>'Profile Picture',
                    'required' => false,
                    'data_class' => null,
                    'constraints' =>  new File(maxSize: '1M', mimeTypes: [
                        'image/jpeg',
                        'image/png',
                    ])
                ]
            )
            ->add('isActive');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
