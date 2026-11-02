<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Bar;
use Citadel\Aureum\Core\Entity\BarStyle;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\DressCode;
use Citadel\Aureum\Core\Entity\Enum\EntryPolicy;
use Citadel\Aureum\Core\Entity\Tag;
use Citadel\Aureum\Core\Repository\BarStyleRepository;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\TagRepository;
use Citadel\Aureum\Core\Validator\ValidOpeningTimes;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class BarType extends AbstractType
{
    public function __construct(
        private readonly BarStyleRepository $barStyleRepository,
        private readonly TagRepository $tagRepository,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bar::class,
            'hotel' => null,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Bar Name',
                'attr' => [
                    'placeholder' => 'Bar Name',
                ],
            ])
            ->add('styles', EntityType::class, [
                'label' => 'Bar Style',
                'class' => BarStyle::class,
                'multiple' => true,
                'autocomplete' => true,
                'choices' => $this->barStyleRepository->findBy(['hotel' => $options['hotel']], ['name' => 'ASC']),
                'choice_label' => 'name',
                'attr' => [
                    'placeholder' => 'Bar Style',
                ],
                'required' => false,
            ])
            ->add('neighbourhood', TextType::class, [
                'label' => 'Neighbourhood',
                'attr' => [
                    'placeholder' => 'Neighbourhood',
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Street',
                'attr' => [
                    'placeholder' => 'Street',
                ],
            ])
            ->add('dressCode', EnumType::class, [
                'label' => 'Dress Code',
                'class' => DressCode::class,
                'choice_label' => static fn (DressCode $dressCode) => $dressCode->getLabel(),
                'placeholder' => 'No dress code',
                'required' => false,
            ])
            ->add('entryPolicy', EnumType::class, [
                'label' => 'Walk-ins / Reservations',
                'class' => EntryPolicy::class,
                'choice_label' => static fn (EntryPolicy $policy) => $policy->getLabel(),
                'placeholder' => 'Unknown',
                'required' => false,
            ])
            ->add('happyHour', TextType::class, [
                'label' => 'Happy Hour',
                'required' => false,
                'constraints' => [new Length(max: 255)],
                'attr' => [
                    'placeholder' => 'e.g. Mon–Fri 5–7pm, 2-for-1 cocktails',
                    'maxlength' => 255,
                ],
            ])
            ->add('connections', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => 'name',
                'label' => 'Concierge Connections',
                'multiple' => true,
                'autocomplete' => true,
                'query_builder' => static fn (EmployeeRepository $repository) => $repository
                    ->createQueryBuilder('e')
                    ->where('e.hotel = :hotel')
                    ->andWhere('e.archivedAt IS NULL')
                    ->setParameter('hotel', $options['hotel'])
                    ->orderBy('e.name', 'ASC'),
                'attr' => [
                    'placeholder' => 'Select Concierge, Leave Blank If No Connections',
                ],
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choices' => $this->tagRepository->findBy(['hotel' => $options['hotel']], ['name' => 'ASC']),
                'choice_label' => 'name',
                'label' => 'Tags',
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Tags',
                ],
                'required' => false,
            ])
            ->add('openingTimes', HiddenType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [new ValidOpeningTimes()],
            ])
        ;

        $builder->get('openingTimes')->addModelTransformer(new CallbackTransformer(
            static fn (?array $value) => $value === null ? '' : json_encode($value),
            static function (?string $value) {
                if ($value === null || trim($value) === '') {
                    return null;
                }

                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : null;
            },
        ));
    }
}
