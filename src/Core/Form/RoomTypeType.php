<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\RoomType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RoomType::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'help' => 'Short code, e.g. TVUN.',
                'attr' => [
                    'placeholder' => 'Code',
                    'maxlength' => 20,
                ],
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'Name',
                ],
            ])
            ->add('archived', CheckboxType::class, [
                'required' => false,
                'help' => 'Archived types stay on existing rooms but cannot be picked for new ones.',
            ])
        ;
    }
}
