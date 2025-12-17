<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\Type;      // Ton enum Type
use App\Enum\Statut;    // Ton enum Statut (si tu en as un)
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('nom', TextType::class, [
        'label' => 'First Name', // affichage en anglais
    ])
    ->add('prenom', TextType::class, [
        'label' => 'Last Name',
    ])
    ->add('password', PasswordType::class, [
        'label' => 'Password',
    ])
    ->add('tel', TextType::class, [
        'label' => 'Phone',
    ])
    ->add('email', EmailType::class, [
        'label' => 'Email',
    ]);
            /*->add('Type', ChoiceType::class, [
                'choices' => [
                    'Admin' => Type::Admin,
                    'Client' => Type::Client,
                    // ajoute d’autres types si nécessaire
                ],
                'placeholder' => 'Choisissez un type',
            ])*/
            /*->add('Statut', ChoiceType::class, [
                'choices' => [
                    'Actif' => Statut::ACTIVE,
                    'Inactif' => Statut::INACTIVE,
                    'Banned' => Statut::BANNED,
                    // ajoute d’autres statuts si nécessaire
                ],
                'placeholder' => 'Choisissez un statut',
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
