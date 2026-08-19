<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Booking;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\BookingStatus;
use Citadel\Aureum\Core\Entity\Enum\BookingType;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class BookingEditType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'userTimezone' => 'UTC',
        ]);
        $resolver->setRequired('hotel');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => BookingType::choices(),
                'choice_value' => static fn (?BookingType $type) => $type?->value,
                'expanded' => true,
                'multiple' => false,
                'disabled' => true,
                'choice_attr' => static fn () => [
                    'data-booking-form-target' => 'typeOption',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => BookingStatus::choices(),
            ])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'model_timezone' => 'UTC',
                'view_timezone' => $options['userTimezone'],
            ])
            ->add('middleman', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => 'name',
                'label' => 'Concierge',
                'query_builder' => fn (EmployeeRepository $repository) => $repository
                    ->createQueryBuilder('e')
                    ->where('e.hotel = :hotel')
                    ->setParameter('hotel', $options['hotel'])
                    ->orderBy('e.name', 'ASC'),
            ])
            ->add('vendor', TextType::class, [
                'label' => 'Supplier',
                'attr' => [
                    'placeholder' => 'Supplier',
                    'data-booking-form-target' => 'vendor',
                    'data-vendor-labels' => json_encode(BookingType::vendorLabels()),
                ],
                'required' => false,
            ])
            ->add('reference', TextType::class, [
                'label' => 'Reference',
                'attr' => [
                    'placeholder' => 'Confirmation Reference',
                ],
                'required' => false,
            ])
            ->add('cost', TextType::class, [
                'label' => 'Cost',
                'attr' => [
                    'placeholder' => 'Booking Cost',
                ],
                'required' => false,
            ])
            ->add('details', BookingDetailsType::class, [
                'by_reference' => false,
            ])
            ->add('guest', TextType::class, [
                'label' => 'Guest Name',
                'attr' => [
                    'placeholder' => 'Guest Name',
                ],
            ])
            ->add('number', TextType::class, [
                'label' => 'Guest Number',
                'attr' => [
                    'placeholder' => 'Guest Number',
                ],
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'Guest Email',
                'attr' => [
                    'placeholder' => 'Guest Email',
                ],
                'required' => false,
            ])
            ->add('notes', TextType::class, [
                'label' => 'Notes',
                'attr' => [
                    'placeholder' => 'Notes',
                    'maxlength' => 255,
                ],
                'help' => 'Operational details only. Do not record health information or allegations of misconduct here.',
                'constraints' => [new Length(max: 255)],
                'required' => false,
            ]);
    }
}
