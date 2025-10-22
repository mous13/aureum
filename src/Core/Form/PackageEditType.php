<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Package;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PackageEditType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Package::class
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', CheckBoxType::class, [
                'label' => 'Mark as Picked Up',
                'required' => false,
                'data' => false,
                'mapped' => false,
            ])
            ->add('name', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Guest Name'
                ]
            ])
            ->add('description', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Package Description'
                ]
            ])
            ->add('location', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Location Stored'
                ]
            ])
            ->add('note', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Notes'
                ],
                'required' => false
            ]);
    }
}
