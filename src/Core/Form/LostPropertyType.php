<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\LostPropertyClass;
use Citadel\Aureum\Core\Entity\Enum\LostPropertyStatus;
use Citadel\Aureum\Core\Entity\LostProperty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class LostPropertyType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LostProperty::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Lost' => LostPropertyClass::LOST,
                    'Found' => LostPropertyClass::FOUND,
                ],
                'choice_value' => static fn (?LostPropertyClass $type) => $type?->value,
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Open' => LostPropertyStatus::OPEN,
                    'Collected' => LostPropertyStatus::COLLECTED,
                    'Posted' => LostPropertyStatus::POSTED,
                    'Disposed' => LostPropertyStatus::DISPOSED,
                    'Waiting for collection' => LostPropertyStatus::WAITING_COLLECTION,
                    'Waiting to be posted' => LostPropertyStatus::WAITING_POSTED,
                    'Stored' => LostPropertyStatus::STORED,
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'What does it look like',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => [
                    'placeholder' => 'What room or area was it lost/found',
                ],
            ])
            ->add('storedLocation', TextType::class, [
                'label' => 'Stored Location',
                'required' => false,
                'attr' => [
                    'placeholder' => 'If found, where is it stored?',
                ],
            ])
            ->add('guest', TextType::class, [
                'label' => 'Guest Name',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Guest Name',
                ],
            ])
            ->add('contact', TextType::class, [
                'label' => 'Guest Contact',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Guest Email or Number',
                ],
            ])
            ->add('note', TextType::class, [
                'label' => 'Notes',
                'attr' => [
                    'placeholder' => 'Add a note',
                    'maxlength' => 255,
                ],
                'help' => 'Operational details only. Do not record health information or allegations of misconduct here.',
                'constraints' => [new Length(max: 255)],
                'required' => false,
            ]);
    }
}
