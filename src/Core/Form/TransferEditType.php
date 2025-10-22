<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\FineStatus;
use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\Transfer;
use Citadel\Aureum\Core\Entity\TransferStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TransferEditType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Transfer::class
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Unconfirmed' => TransferStatus::UNCONFIRMED,
                    'Confirmed' => TransferStatus::CONFIRMED,
                    'Completed' => TransferStatus::COMPLETED,
                    'Cancelled' => TransferStatus::CANCELLED,
                ],
            ])
            ->add('notes', TextType::class, [
                'label' => 'Notes',
                'attr' => [
                    'placeholder' => 'Notes'
                ],
                'required' => false
            ])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('guest', TextType::class, [
                'label' => 'Guest Name',
                'attr' => [
                    'placeholder' => 'Guest Name'
                ]
            ])
            ->add('number', TextType::class, [
                'label' => 'Guest Number',
                'attr' => [
                    'placeholder' => 'Guest Number'
                ]
            ])
            ->add('email', TextType::class, [
                'label' => 'Guest Email',
                'attr' => [
                    'placeholder' => 'Guest Email'
                ]
            ])
            ->add('pickup', TextType::class, [
                'label' => 'Pickup Location',
                'attr' => [
                    'placeholder' => 'Pickup Location'
                ]
            ])
            ->add('dropoff', TextType::class, [
                'label' => 'Drop Off Location',
                'attr' => [
                    'placeholder' => 'Drop Off Location'
                ]
            ])
            ->add('driver', TextType::class, [
                'label' => 'Driver',
                'attr' => [
                    'placeholder' => 'Driver Name'
                ]
            ])
            ->add('cost', TextType::class, [
                'label' => 'Cost',
                'attr' => [
                    'placeholder' => 'Job Cost'
                ]
            ]);
    }
}
