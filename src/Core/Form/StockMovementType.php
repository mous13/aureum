<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\MovementDirection;
use Citadel\Aureum\Core\Entity\Enum\MovementReason;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StockMovement;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockMovementType extends AbstractType
{
    /**
     * @return array<string, MovementReason>
     */
    public static function reasonChoices(): array
    {
        $choices = [];
        foreach (MovementReason::cases() as $case) {
            $choices[$case->getLabel()] = $case;
        }

        return $choices;
    }

    /**
     * @return array<string, MovementDirection>
     */
    public static function directionChoices(): array
    {
        $choices = [];
        foreach (MovementDirection::cases() as $case) {
            $choices[$case->getLabel()] = $case;
        }

        return $choices;
    }

    public static function directionFor(MovementReason $reason, MovementDirection $chosen): MovementDirection
    {
        return $reason->defaultDirection() ?? $chosen;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => StockMovement::class])
            ->setRequired(['items', 'locations']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('item', EntityType::class, [
                'class' => InventoryItem::class,
                'choices' => $options['items'],
                'choice_label' => 'name',
            ])
            ->add('reason', ChoiceType::class, [
                'choices' => self::reasonChoices(),
            ])
            ->add('direction', ChoiceType::class, [
                'choices' => self::directionChoices(),
                'help' => 'Only used for Adjustment. Every other reason sets the direction automatically.',
            ])
            ->add('quantity', IntegerType::class, [
                'help' => 'In base units.',
            ])
            ->add('destination', EntityType::class, [
                'class' => StorageLocation::class,
                'choices' => $options['locations'],
                'choice_label' => 'name',
                'required' => false,
                'help' => 'Required for Transfer.',
            ])
            ->add('occurredAt', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
            ]);
    }
}
