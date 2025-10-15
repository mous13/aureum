<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Entity\LostPropertyClass;
use Citadel\Aureum\Core\Entity\LostPropertyStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LostPropertyType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LostProperty::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => '',
                'choices' => [
                    'Lost' => LostPropertyClass::LOST,
                    'Found' => LostPropertyClass::FOUND,
                ],
                'placeholder' => 'Select Type',
            ])
            ->add('description', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'What does it look like',
                ]
            ])
            ->add('location', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'What room or area was it lost/found',
                ]
            ])
            ->add('storedLocation', TextType::class, [
                'label' => '',
                'required' => false,
                'attr' => [
                    'placeholder' => 'If found, where is it stored?',
                ]
            ])
            ->add('reportedBy', HiddenType::class, [
            ])
            ->add('guest', TextType::class, [
                'label' => '',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Guest Name',
                ]
            ])
            ->add('contact', TextType::class, [
                'label' => '',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Guest Email or Number',
                ]
            ])
            ->add('note', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Add a note',
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => '',
                'choices' => [
                    'Open' => LostPropertyStatus::OPEN,
                    'Collected' => LostPropertyStatus::COLLECTED,
                    'Posted' => LostPropertyStatus::POSTED,
                    'Disposed' => LostPropertyStatus::DISPOSED,
                    'Waiting for collection' => LostPropertyStatus::WAITING_COLLECTION,
                    'Waiting to be posted' => LostPropertyStatus::WAITING_POSTED,
                    'Stored' => LostPropertyStatus::STORED,
                ]
            ]);
    }
}
