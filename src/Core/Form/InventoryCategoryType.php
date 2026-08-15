<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Entity\InventoryCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventoryCategoryType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => InventoryCategory::class])
            ->setRequired('inventories');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inventory', EntityType::class, [
                'class' => Inventory::class,
                'choices' => $options['inventories'],
                'choice_label' => 'name',
            ])
            ->add('name', TextType::class, ['attr' => ['placeholder' => 'e.g. Essentials']])
            ->add('position', IntegerType::class, ['required' => false]);
    }
}
