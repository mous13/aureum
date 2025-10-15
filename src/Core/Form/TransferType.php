<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\EmployeeRole;
use Citadel\Aureum\Core\Entity\Transfer;
use Citadel\Aureum\Core\Entity\TransferStatus;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TransferType extends AbstractType
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
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('guest', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Guest Name'
                ]
            ])
            ->add('number', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Guest Number'
                ]
            ])
            ->add('email', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Guest Email'
                ]
            ])
            ->add('pickup', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Pickup Location'
                ]
            ])
            ->add('dropoff', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Drop Off Location'
                ]
            ])
            ->add('middleman', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => 'name',
                'label' => '',
                'query_builder' => function (EmployeeRepository $repository) {
                    return $repository->createQueryBuilder('e')
                        ->where('e.role = :role')
                        ->setParameter('role', EmployeeRole::CONCIERGE);
                },
                'placeholder' => 'Select Concierge',
            ])
            ->add('driver', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Driver Name'
                ]
            ])
            ->add('cost', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Job Cost'
                ]
            ])
            ->add('notes', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Notes'
                ],
                'required' => false
            ])
        ->add('status', ChoiceType::class, [
            'label' => '',
            'choices' => [
                'Unconfirmed' => TransferStatus::UNCONFIRMED,
                'Confirmed' => TransferStatus::CONFIRMED,
                'Completed' => TransferStatus::COMPLETED,
                'Cancelled' => TransferStatus::CANCELLED,
            ],
            'placeholder' => 'Select Status',
        ]);
    }
}
