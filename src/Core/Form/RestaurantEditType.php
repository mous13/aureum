<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Cuisine;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\DietaryRequirements;
use Citadel\Aureum\Core\Entity\Enum\EmployeeRole;
use Citadel\Aureum\Core\Entity\Enum\MealPeriods;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Entity\Tag;
use Citadel\Aureum\Core\Repository\CuisineRepository;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Citadel\Aureum\Core\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RestaurantEditType extends AbstractType
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
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Restaurant Name',
                ],
            ])
            ->add('cuisines', EntityType::class, [
                'label' => '',
                'class' => Cuisine::class,
                'multiple' => true,
                'autocomplete' => true,
                'choices' => $this->cuisineRepository->findBy(['hotel' => $options['hotel']]),
                'choice_label' => 'name',
                'attr' => [
                    'placeholder' => 'Cuisine',
                ],
            ])
            ->add('neighbourhood', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Neighbourhood',
                ],
            ])
            ->add('street', TextType::class, [
                'label' => '',
                'attr' => [
                    'placeholder' => 'Street',
                ],
            ])
            ->add('mealPeriods', EnumType::class, [
                'label' => '',
                'class' => MealPeriods::class,
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Meal Periods',
                ],
                'choice_label' => fn(MealPeriods $periods) => $periods->value,

            ])
            ->add('dietaryRequirements', EnumType::class, [
                'label' => '',
                'class' => DietaryRequirements::class,
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Dietary Requirements',
                ],
                'choice_label' => fn(DietaryRequirements $requirements) => $requirements->value,
            ])
            ->add('connections', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => 'name',
                'label' => '',
                'multiple' => true,
                'autocomplete' => true,
                'query_builder' => function (EmployeeRepository $repository) use ($options) {
                    return $repository->createQueryBuilder('e')
                        ->where('e.role = :role')
                        ->andWhere('e.hotel = :hotel')
                        ->setParameter('role', EmployeeRole::CONCIERGE)
                        ->setParameter('hotel', $options['hotel']);
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
                'label' => '',
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Tags',
                ],
            ])
        ;
    }
}
