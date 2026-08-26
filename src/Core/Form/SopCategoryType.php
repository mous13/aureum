<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\SopCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SopCategoryType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SopCategory::class,
            'hotel' => null,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class);
    }
}
