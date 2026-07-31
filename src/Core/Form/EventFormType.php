<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\EventType;
use Citadel\Aureum\Core\Entity\Event;
use Citadel\Aureum\Core\Entity\Restaurant;
use Citadel\Aureum\Core\Repository\RestaurantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Event::class,
                'hotel' => null,
                'userTimezone' => 'UTC',
            ]
        );

        $resolver->setRequired('hotel');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'help' => 'name of the event',
            ])
            ->add('type', EnumType::class, [
                'class' => EventType::class,
                'choice_label' => static fn(EventType $type) => $type->getLabel(),
            ])
            ->add('start', DateTimeType::class, [
                'widget' => 'single_text',
                'model_timezone' => 'UTC',
                'view_timezone' => $options['userTimezone'],
            ])
            ->add('end', DateTimeType::class, [
                'widget' => 'single_text',
                'model_timezone' => 'UTC',
                'view_timezone' => $options['userTimezone'],
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('link', UrlType::class, [
                'default_protocol' => 'https',
                'required' => false,
            ])
            ->add('restaurant', EntityType::class, [
                'class' => Restaurant::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'No restaurant',
                'query_builder' => static fn (RestaurantRepository $repository) => $repository
                    ->createQueryBuilder('r')
                    ->where('r.hotel = :hotel')
                    ->setParameter('hotel', $options['hotel'])
                    ->orderBy('r.name', 'ASC'),
            ])
        ;
    }
}
