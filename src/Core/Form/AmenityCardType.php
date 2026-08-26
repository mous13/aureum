<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\AmenityCard;
use Citadel\Aureum\Core\Entity\Enum\AmenityCardStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AmenityCardType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AmenityCard::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roomNumber', TextType::class, [
                'label' => 'Room',
                'attr' => ['placeholder' => 'e.g. 412'],
            ])
            ->add('guestLastName', TextType::class, [
                'label' => 'Guest lastname',
                'attr' => ['placeholder' => 'e.g. Whitfield'],
            ])
            ->add('items', TextareaType::class, [
                'label' => 'Items (one per line)',
                'property_path' => 'itemsText',
                'constraints' => [new NotBlank()],
                'attr' => ['rows' => 4, 'placeholder' => "e.g.\n2x Beer\n1x Card"],
            ])
            ->add('status', EnumType::class, [
                'class' => AmenityCardStatus::class,
                'choice_label' => static fn (AmenityCardStatus $status) => $status->getLabel(),
            ])
            ->add('priority', CheckboxType::class, [
                'label' => 'Priority',
                'required' => false,
                'help' => 'Highlight this room so it gets delivered first.',
            ]);
    }
}
