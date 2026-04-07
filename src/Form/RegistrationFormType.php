<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Genre;
use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $nameConstraints = [
            new NotBlank(message: 'Ce champ est obligatoire.'),
            new Length(min: 2, max: 80),
            new Regex([
                'pattern' => '/^[\p{L}\s\'\-\.]+$/u',
                'message' => 'Lettres, espaces, tirets ou apostrophes uniquement.',
            ]),
        ];

        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => $nameConstraints,
                'attr' => ['placeholder' => 'Prénom', 'autocomplete' => 'given-name', 'maxlength' => 80, 'class' => 'form-control rounded-3'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => $nameConstraints,
                'attr' => ['placeholder' => 'Nom', 'autocomplete' => 'family-name', 'maxlength' => 80, 'class' => 'form-control rounded-3'],
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'Indiquez votre email.'),
                    new Email(message: 'Email invalide.'),
                    new Length(max: 100),
                ],
                'attr' => ['placeholder' => 'vous@exemple.com', 'autocomplete' => 'email', 'maxlength' => 100, 'class' => 'form-control rounded-3'],
            ])
            ->add('genre', EntityType::class, [
                'class' => Genre::class,
                'choice_label' => 'sexe',
                'label' => 'Genre',
                'placeholder' => '— Choisir —',
                'required' => false,
                'attr' => ['class' => 'form-select rounded-3'],
            ])
            ->add('role', EnumType::class, [
                'class' => UserRole::class,
                'label' => 'Type de compte',
                'constraints' => [new NotBlank(message: 'Choisissez un type de compte.')],
                'choices' => [
                    'Participant' => UserRole::PARTICIPANT,
                    'Organisateur' => UserRole::ORGANISATEUR,
                    'Administrateur' => UserRole::ADMIN,
                ],
                'attr' => ['class' => 'form-select rounded-3'],
                'help' => 'Choisissez votre profil. L’administration est réservée aux comptes autorisés en production.',
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])],
                'attr' => ['accept' => 'image/*', 'class' => 'form-control rounded-3 d-none js-register-photo'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['autocomplete' => 'new-password', 'placeholder' => '••••••••', 'class' => 'form-control rounded-3', 'minlength' => 6, 'maxlength' => 128],
                    'constraints' => [
                        new NotBlank(message: 'Choisissez un mot de passe.'),
                        new Length(min: 6, max: 128, minMessage: 'Au moins {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.'),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmation',
                    'attr' => ['autocomplete' => 'new-password', 'placeholder' => '••••••••', 'class' => 'form-control rounded-3', 'minlength' => 6, 'maxlength' => 128],
                    'constraints' => [new NotBlank(message: 'Confirmez le mot de passe.')],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
