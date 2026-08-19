<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Form;

use Citadel\Aureum\Core\Entity\Employee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                'label' => 'Full Name',
            ])
            ->add('hotelAdmin', CheckboxType::class, [
                'label' => 'Hotel admin',
                'required' => false,
                'help' => 'Hotel admins have every permission and manage roles and modules for their hotel.',
            ])
            ->add('verified', CheckboxType::class, [
                'label' => 'email verified',
                'required' => false,
                'mapped' => false,
            ]);
    }
}
