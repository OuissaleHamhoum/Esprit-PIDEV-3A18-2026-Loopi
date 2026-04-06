<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Genre;
use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $nameConstraints = [
            new NotBlank(message: 'Ce champ est obligatoire.'),
            new Length(min: 2, max: 80, minMessage: 'Au moins {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.'),
            new Regex([
                'pattern' => '/^[\p{L}\s\'\-\.]+$/u',
                'message' => 'Utilisez uniquement des lettres, espaces, tirets ou apostrophes.',
            ]),
        ];

        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => $nameConstraints,
                'attr' => ['maxlength' => 80, 'class' => 'form-control rounded-3'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => $nameConstraints,
                'attr' => ['maxlength' => 80, 'class' => 'form-control rounded-3'],
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'L’email est obligatoire.'),
                    new Email(message: 'Adresse email invalide.'),
                    new Length(max: 100),
                ],
                'attr' => ['maxlength' => 100, 'class' => 'form-control rounded-3', 'autocomplete' => 'email'],
            ])
            ->add('role', EnumType::class, [
                'class' => UserRole::class,
                'label' => 'Rôle',
                'constraints' => [new NotBlank(message: 'Choisissez un rôle.')],
                'choices' => [
                    'Administrateur' => UserRole::ADMIN,
                    'Organisateur' => UserRole::ORGANISATEUR,
                    'Participant' => UserRole::PARTICIPANT,
                ],
                'attr' => ['class' => 'form-select rounded-3'],
            ])
            ->add('genre', EntityType::class, [
                'class' => Genre::class,
                'choice_label' => 'sexe',
                'label' => 'Genre',
                'required' => false,
                'placeholder' => '—',
                'attr' => ['class' => 'form-select rounded-3'],
            ]);

        if ($options['include_password']) {
            $builder->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'required' => $options['password_required'],
                'constraints' => $options['password_required']
                    ? [
                        new NotBlank(message: 'Le mot de passe est obligatoire.'),
                        new Length(min: 6, max: 128, minMessage: 'Au moins {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.'),
                    ]
                    : [
                        new Length(max: 128, maxMessage: 'Maximum {{ limit }} caractères.'),
                    ],
                'attr' => array_merge([
                    'class' => 'form-control rounded-3',
                    'maxlength' => 128,
                    'autocomplete' => 'new-password',
                ], $options['password_required'] ? ['minlength' => 6] : []),
                'help' => $options['password_required'] ? null : 'Laisser vide pour conserver le mot de passe actuel.',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password' => true,
            'password_required' => false,
        ]);
    }
}
