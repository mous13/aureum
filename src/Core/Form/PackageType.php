<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Package;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackageType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Package::class,
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Guest Name',
                'attr' => [
                    'placeholder' => 'Guest Name',
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Package Description',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location Stored',
                'attr' => [
                    'placeholder' => 'Location Stored',
                ],
            ])
            ->add('note', TextType::class, [
                'label' => 'Notes',
                'attr' => [
                    'placeholder' => 'Notes',
                ],
                'required' => false,
            ]);
    }
}
