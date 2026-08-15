<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\HotelRole;
use Citadel\Aureum\Core\Form\DTO\NewStaff;
use Citadel\Aureum\Core\Repository\HotelRoleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewStaffType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => NewStaff::class]);
        $resolver->setRequired('hotel');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full name',
                'attr' => ['placeholder' => 'e.g. Sam Whitfield'],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'help' => 'What they type to sign in. This cannot be changed later.',
                'attr' => ['placeholder' => 'e.g. swhitfield'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'help' => 'Used for password resets.',
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'Timezone',
                'required' => false,
                'placeholder' => 'Same as the hotel',
            ])
            ->add('roles', EntityType::class, [
                'class' => HotelRole::class,
                'label' => 'Roles',
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'by_reference' => false,
                'help' => 'What this employee can see and do. You can change this later.',
                'query_builder' => static fn (HotelRoleRepository $repository) => $repository
                    ->createQueryBuilder('r')
                    ->andWhere('r.hotel = :hotel')
                    ->setParameter('hotel', $options['hotel'])
                    ->orderBy('r.name', 'ASC'),
            ])
        ;
    }
}
