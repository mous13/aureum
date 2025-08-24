<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Form;

use Citadel\Aureum\Admin\Form\DTO\NewEmployee;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeType extends AbstractType
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly HotelRepository $hotelRepository,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewEmployee::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $hotelId = null;
        if ($request && $request->attributes->has('hotelId')) {
            $hotelId = (int) $request->attributes->get('hotelId');
        }

        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => ['autocomplete' => 'username', 'autofocus' => 'autofocus'],
                'help' => 'This will be their login username',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => ['autocomplete' => 'new-password']
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => ['autocomplete' => 'new-password']
                ],
            ])
            ->add('timezone', ChoiceType::class, [
                'label' => 'Timezone',
                'autocomplete' => true,
                'placeholder' => 'Select a timezone',
                'choices' => $this->getTimezones(),
                'attr' => [
                    'data-controller' => 'forumify--forumify-platform--client-timezone'
                ]
            ])

            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'help' => 'The employee\'s display name',
            ])
            ->add('role', TextType::class, [
                'label' => 'Role/Position',
                'help' => 'e.g., Front Desk Manager, Concierge, etc.',
            ]);

        if ($hotelId !== null) {
            $hotel = $this->hotelRepository->find($hotelId);
            $builder->add('hotel', EntityType::class, [
                'class' => Hotel::class,
                'choice_label' => 'name',
                'data' => $hotel,
                'attr' => ['style' => 'display: none;'],
                'label' => false,
            ]);
        } else {
            $builder->add('hotel', EntityType::class, [
                'class' => Hotel::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a hotel',
                'label' => 'Hotel',
            ]);
        }
    }

    private function getTimezones(): array
    {
        $timezones = \DateTimeZone::listIdentifiers();
        return array_combine($timezones, $timezones);
    }
}
