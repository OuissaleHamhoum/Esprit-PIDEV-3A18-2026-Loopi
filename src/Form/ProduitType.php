<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CategoryProduit;
use App\Entity\Produit;
use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $hideOwner = $options['hide_owner'];
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Indiquez le nom du produit.'),
                    new Length(min: 2, max: 200),
                ],
                'attr' => ['maxlength' => 200, 'class' => 'form-control rounded-3'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [new Length(max: 8000)],
                'attr' => ['rows' => 5, 'maxlength' => 8000, 'class' => 'form-control rounded-3'],
            ])
            ->add('category', EntityType::class, [
                'class' => CategoryProduit::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                'constraints' => [new NotBlank(message: 'Choisissez une catégorie.')],
                'attr' => ['class' => 'form-select rounded-3'],
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
                'choice_label' => fn (User $u) => $u->getPrenom().' '.$u->getNom().' ('.$u->getEmail().')',
                'label' => 'Organisateur',
                'required' => false,
                'placeholder' => '—',
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
            'data_class' => Produit::class,
            'hide_owner' => false,
        ]);
    }
}
