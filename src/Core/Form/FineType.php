<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\FineStatus;
use Citadel\Aureum\Core\Entity\Fine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class FineType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Fine::class,
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('number', TextType::class, [
                'label' => 'Fine Number',
                'attr' => [
                    'placeholder' => 'Fine Number',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Guest Name',
                'attr' => [
                    'placeholder' => 'Guest Name',
                ],
            ])
            ->add('email', TextType::class, [
                'label' => 'Guest Email',
                'attr' => [
                    'placeholder' => 'Guest Email',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Not Appealed' => FineStatus::NOT_APPEALED,
                    'Appeal Submitted' => FineStatus::APPEAL_SUBMITTED,
                    'Appeal Completed' => FineStatus::APPEAL_COMPLETED,
                ],
                'placeholder' => 'Select Status',
            ])
            ->add('note', TextType::class, [
                'label' => 'Notes',
                'attr' => [
                    'placeholder' => 'Notes',
                    'maxlength' => 255,
                ],
                'help' => 'Operational details only. Do not record health information or allegations of misconduct here.',
                'constraints' => [new Length(max: 255)],
                'required' => false,
            ]);
    }
}
