<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Collection;
use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

final class CollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $hideOwner = $options['hide_owner'];
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(message: 'Indiquez un titre.'),
                    new Length(min: 2, max: 255),
                ],
                'attr' => ['maxlength' => 255, 'class' => 'form-control rounded-3'],
            ])
            ->add('materialType', TextType::class, [
                'label' => 'Type de matériau',
                'constraints' => [
                    new NotBlank(message: 'Indiquez le type de matériau.'),
                    new Length(min: 2, max: 255),
                ],
                'attr' => ['maxlength' => 255, 'class' => 'form-control rounded-3', 'placeholder' => 'ex. Verre, Papier…'],
            ])
            ->add('goalAmount', NumberType::class, [
                'label' => 'Objectif',
                'constraints' => [
                    new NotBlank(message: 'Indiquez l’objectif.'),
                    new Positive(message: 'L’objectif doit être un nombre strictement positif.'),
                ],
                'attr' => ['min' => 0.01, 'step' => 'any', 'class' => 'form-control rounded-3'],
            ])
            ->add('currentAmount', NumberType::class, [
                'label' => 'Collecté',
                'required' => false,
                'empty_data' => null,
                'constraints' => [new PositiveOrZero(message: 'Le montant collecté ne peut pas être négatif.')],
                'attr' => ['min' => 0, 'step' => 'any', 'class' => 'form-control rounded-3'],
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unité',
                'constraints' => [
                    new NotBlank(message: 'Indiquez l’unité (ex. kg).'),
                    new Length(max: 50),
                ],
                'attr' => ['maxlength' => 50, 'class' => 'form-control rounded-3', 'placeholder' => 'kg'],
            ])
            ->add('status', TextType::class, [
                'label' => 'Statut',
                'constraints' => [
                    new NotBlank(message: 'Indiquez un statut.'),
                    new Length(min: 2, max: 50),
                ],
                'attr' => ['maxlength' => 50, 'class' => 'form-control rounded-3', 'placeholder' => 'ex. active, termine'],
                'help' => 'Ex. : active, termine, annule (comme en base de données).',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Image(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])],
                'attr' => ['class' => 'form-control rounded-3', 'accept' => 'image/*'],
            ]);

        if (!$hideOwner) {
            $builder->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getPrenom().' '.$u->getNom(),
                'label' => 'Organisateur',
                'constraints' => [new NotBlank(message: 'Choisissez un organisateur.')],
                'attr' => ['class' => 'form-select rounded-3'],
                'query_builder' => fn ($r) => $r->createQueryBuilder('u')
                    ->where('u.role = :role')
                    ->setParameter('role', UserRole::ORGANISATEUR),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collection::class,
            'hide_owner' => false,
        ]);
    }
}
