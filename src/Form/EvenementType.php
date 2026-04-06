<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

final class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $hideOrg = $options['hide_organisateur'];
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(message: 'Indiquez un titre.'),
                    new Length(min: 3, max: 200),
                ],
                'attr' => ['maxlength' => 200, 'class' => 'form-control rounded-3'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [new Length(max: 10000)],
                'attr' => ['rows' => 4, 'maxlength' => 10000, 'class' => 'form-control rounded-3'],
            ])
            ->add('dateEvenement', DateTimeType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'constraints' => [new NotBlank(message: 'Choisissez la date.')],
                'attr' => ['class' => 'form-control rounded-3'],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'constraints' => [new Length(max: 200)],
                'attr' => ['maxlength' => 200, 'class' => 'form-control rounded-3'],
            ])
            ->add('capaciteMax', IntegerType::class, [
                'label' => 'Capacité max',
                'required' => false,
                'empty_data' => null,
                'constraints' => [new Range(min: 1, max: 100000, notInRangeMessage: 'La capacité doit être entre {{ min }} et {{ max }}.')],
                'attr' => ['min' => 1, 'max' => 100000, 'class' => 'form-control rounded-3'],
            ])
            ->add('statutValidation', TextType::class, [
                'label' => 'Statut validation',
                'required' => false,
                'empty_data' => null,
                'constraints' => [new Length(max: 32)],
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'placeholder' => 'en_attente, approuve, refuse…',
                    'maxlength' => 32,
                ],
                'help' => 'Valeurs usuelles : en_attente, approuve, refuse (identique à la base de données).',
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'required' => false,
                'empty_data' => null,
                'constraints' => [new Range(min: -90, max: 90, notInRangeMessage: 'Latitude entre -90 et 90.')],
                'attr' => ['step' => 'any', 'class' => 'form-control rounded-3', 'placeholder' => 'ex. 36.8065'],
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'required' => false,
                'empty_data' => null,
                'constraints' => [new Range(min: -180, max: 180, notInRangeMessage: 'Longitude entre -180 et 180.')],
                'attr' => ['step' => 'any', 'class' => 'form-control rounded-3', 'placeholder' => 'ex. 10.1815'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Image(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])],
                'attr' => ['class' => 'form-control rounded-3', 'accept' => 'image/*'],
            ]);

        if (!$hideOrg) {
            $builder->add('organisateur', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getPrenom().' '.$u->getNom(),
                'label' => 'Organisateur',
                'constraints' => [new NotBlank(message: 'Choisissez un organisateur.')],
                'attr' => ['class' => 'form-select rounded-3'],
                'query_builder' => fn ($r) => $r->createQueryBuilder('u')
                    ->where('u.role = :r')
                    ->setParameter('r', UserRole::ORGANISATEUR),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'hide_organisateur' => false,
        ]);
    }
}
