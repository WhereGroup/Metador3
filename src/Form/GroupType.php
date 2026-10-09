<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', TextType::class, ['label' => 'Gruppe'])
            ->add('description', TextareaType::class, [
                'label'    => 'Beschreibung',
                'required' => false
            ])
            ->add(
                'users',
                EntityType::class,
                [
                    'class'        =>  User::class,
                    'multiple'     => true,
                    'choice_label' => 'username',
                    'label'        => 'Benutzer',
                    'required'     => false
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class
        ]);
    }
}
