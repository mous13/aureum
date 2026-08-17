<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\BookingField;
use Citadel\Aureum\Core\Entity\Enum\BookingType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingDetailsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'required' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (BookingField::cases() as $field) {
            $types = array_map(static fn (BookingType $type) => $type->value, $field->getTypes());

            $builder->add($field->value, TextType::class, [
                'label' => $field->getLabel(),
                'required' => false,
                'attr' => [
                    'placeholder' => $field->getPlaceholder(),
                    'data-booking-types' => implode(' ', $types),
                ],
            ]);
        }
    }
}
