<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\HotelRole;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class HotelRoleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HotelRole::class,
        ]);
        $resolver->setRequired('hotel');
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function permissionChoices(): array
    {
        $choices = [];
        foreach (Module::cases() as $module) {
            $actions = [];
            foreach ($module->permissions() as $action) {
                $actions[ucfirst($action)] = "{$module->value}.{$action}";
            }

            $choices[$module->getLabel()] = $actions;
        }

        return $choices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $permissionChoices = self::permissionChoices();

        $builder
            ->add('name', TextType::class, [
                'attr' => ['placeholder' => 'e.g. Front Desk', 'maxlength' => 100],
            ])
            ->add('permissions', ChoiceType::class, [
                'choices' => $permissionChoices,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'help' => 'Each level includes the ones below it. Hotel admins (Managers) always have every permission.',
            ])
            ->add('employees', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'by_reference' => false,
                'query_builder' => static fn (EmployeeRepository $repository) => $repository
                    ->createQueryBuilder('e')
                    ->andWhere('e.hotel = :hotel')
                    ->setParameter('hotel', $options['hotel'])
                    ->orderBy('e.name', 'ASC'),
            ])
        ;
    }
}
