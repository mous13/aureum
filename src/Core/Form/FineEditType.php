<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\FineStatus;
use Citadel\Aureum\Core\Entity\Fine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FineEditType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Fine::class
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('number', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Fine Number'
                ],
                'required' => false
            ])
            ->add('name', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Guest Name'
                ]
            ])
            ->add('email', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Guest Email'
                ]
            ])
            ->add('note', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Notes'
                ],
                'required' => false
            ])
            ->add('status', ChoiceType::class, [
                'label' => '',
                'choices' => [
                    'Not Appealed' => FineStatus::NOT_APPEALED,
                    'Appeal Submitted' => FineStatus::APPEAL_SUBMITTED,
                    'Appeal Completed' => FineStatus::APPEAL_COMPLETED,
                ],
                'placeholder' => 'Select Status',
            ]);
    }
}
