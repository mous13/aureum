<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Cuisine;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\DietaryRequirements;
use Citadel\Aureum\Core\Entity\Enum\MealPeriods;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Entity\Tag;
use Citadel\Aureum\Core\Repository\CuisineRepository;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\TagRepository;
use Citadel\Aureum\Core\Validator\ValidOpeningTimes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class RestaurantType extends AbstractType
{
    public function __construct(
        private readonly CuisineRepository $cuisineRepository,
        private readonly TagRepository $tagRepository,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Restaurant::class,
                'hotel' => null,
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Restaurant Name',
                'attr' => [
                    'placeholder' => 'Restaurant Name',
                ],
            ])
            ->add('cuisines', EntityType::class, [
                'label' => 'Cuisine',
                'class' => Cuisine::class,
                'multiple' => true,
                'autocomplete' => true,
                'choices' => $this->cuisineRepository->findBy(['hotel' => $options['hotel']]),
                'choice_label' => 'name',
                'attr' => [
                    'placeholder' => 'Cuisine',
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
            ->add('mealPeriods', EnumType::class, [
                'label' => 'Meal Periods',
                'class' => MealPeriods::class,
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Meal Periods',
                ],
                'choice_label' => fn(MealPeriods $periods) => $periods->value,
                'required' => false,
            ])
            ->add('dietaryRequirements', EnumType::class, [
                'label' => 'Dietary Requirements',
                'class' => DietaryRequirements::class,
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Dietary Requirements',
                ],
                'choice_label' => fn(DietaryRequirements $requirements) => $requirements->value,
                'required' => false,
            ])
            ->add('connections', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => 'name',
                'label' => 'Concierge Connections',
                'multiple' => true,
                'autocomplete' => true,
                'query_builder' => function (EmployeeRepository $repository) use ($options) {
                    return $repository->createQueryBuilder('e')
                        ->where('e.hotel = :hotel')
                        ->andWhere('e.archivedAt IS NULL')
                        ->setParameter('hotel', $options['hotel'])
                        ->orderBy('e.name', 'ASC');
                },
                'attr' => [
                    'placeholder' => 'Select Concierge, Leave Blank If No Connections',
                ],
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choices' => $this->tagRepository->findBy(['hotel' => $options['hotel']]),
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

        $this->addEnumTransformer($builder, 'mealPeriods', MealPeriods::class);
        $this->addEnumTransformer($builder, 'dietaryRequirements', DietaryRequirements::class);

        $builder->get('openingTimes')->addModelTransformer(new CallbackTransformer(
            fn(?array $value) => $value === null ? '' : json_encode($value),
            function (?string $value) {
                if ($value === null || trim($value) === '') {
                    return null;
                }

                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : null;
            },
        ));
    }

    private function addEnumTransformer(FormBuilderInterface $builder, string $field, string $enumClass): void
    {
        $builder->get($field)->addModelTransformer(new CallbackTransformer(
            fn($values) => array_map(
                fn($v) => $v instanceof $enumClass ? $v : $enumClass::from($v),
                $values ?? []
            ),
            fn($values) => array_map(
                fn($v) => $v instanceof $enumClass ? $v->value : $v,
                $values ?? []
            ),
        ));
    }
}
