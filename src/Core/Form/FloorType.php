<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Floor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FloorType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Floor::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => '',
                'attr' => ['placeholder' => 'Floor Name (e.g. Lower Ground)'],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'help' => 'Order in the floor navigation, lowest first.',
            ])
            ->add('gridWidth', IntegerType::class, [
                'label' => 'Grid Width',
                'attr' => ['min' => 4, 'max' => 60],
            ])
            ->add('gridHeight', IntegerType::class, [
                'label' => 'Grid Height',
                'attr' => ['min' => 4, 'max' => 60],
            ]);
    }
}
