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
                'label' => '',
                'choices' => [
                    'Unconfirmed' => TransferStatus::UNCONFIRMED,
                    'Confirmed' => TransferStatus::CONFIRMED,
                    'Completed' => TransferStatus::COMPLETED,
                    'Cancelled' => TransferStatus::CANCELLED,
                ],
            ])
            ->add('notes', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Notes'
                ],
                'required' => false
            ]);
    }
}
