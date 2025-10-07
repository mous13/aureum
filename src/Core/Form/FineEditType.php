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
            ->add('status', ChoiceType::class, [
                'label' => '',
                'choices' => [
                    'Not Appealed' => FineStatus::NOT_APPEALED,
                    'Appeal Submitted' => FineStatus::APPEAL_SUBMITTED,
                    'Appeal Completed' => FineStatus::APPEAL_COMPLETED,
                ],
            ])
            ->add('comment', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Notes'
                ],
                'required' => false
            ]);
    }
}
