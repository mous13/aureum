<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\InventoryCategory;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventoryItemType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => InventoryItem::class])
            ->setRequired(['categories', 'locations']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => InventoryCategory::class,
                'choices' => $options['categories'],
                'choice_label' => 'name',
            ])
            ->add('location', EntityType::class, [
                'class' => StorageLocation::class,
                'choices' => $options['locations'],
                'choice_label' => 'name',
                'help' => 'Only bulk stores are forecast.',
            ])
            ->add('name', TextType::class)
            ->add('unit', TextType::class, [
                'attr' => ['placeholder' => 'e.g. card'],
                'help' => 'The single thing you count.',
            ])
            ->add('packSize', IntegerType::class, [
                'required' => false,
                'help' => 'How many units in a pack. Leave blank if the item is not packed.',
            ])
            ->add('packLabel', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'e.g. box'],
            ])
            ->add('leadTimeDays', IntegerType::class, [
                'required' => false,
                'help' => 'Days between placing an order and it arriving. Nothing forecasts until this is set.',
            ])
            ->add('safetyBufferDays', IntegerType::class, [
                'help' => 'Extra days of cover on top of the lead time.',
            ])
            ->add('active', CheckboxType::class, ['required' => false]);
    }
}
