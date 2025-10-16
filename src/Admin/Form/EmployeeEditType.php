<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Form;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\Hotel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class EmployeeEditType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name'
            ])
            ->add('role', TextType::class, [
                'label' => 'Role/Position',
                'help' => 'e.g., Front Desk Manager, Concierge, etc.',
            ])
            ->add('verified', CheckboxType::class, [
                'label' => 'email verified',
                'required' => false,
                'mapped' => false,
            ]);
    }
}
