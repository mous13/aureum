<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Form;

use Citadel\Aureum\Core\Entity\Hotel;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class HotelType extends AbstractType
{
    public function __construct(
        private readonly Packages $packages
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Hotel::class,
                'image_required' => false,
                'constraints' => [
                    new Assert\Callback(self::validateLocation(...)),
                ],
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $imagePreview = empty($options['data']) ? null : $options['data']->getLogo();

        $builder
            ->add('name', TextType::class)
            ->add('code', TextType::class, [
                'help' => 'This can be your property code or a made up code between 5-7 letters',
            ])
            ->add('newImage', FileType::class, [
                'mapped' => false,
                'label' => 'Hotel Logo',
                'help' => 'Recommended size is 134x30.',
                'attr' => [
                    'preview' => $imagePreview
                        ? $this->packages->getUrl($imagePreview, 'aureum.hotel.image')
                        : null,
                ],
                'constraints' => [
                    ...($options['image_required'] ? [
                        new Assert\NotBlank(allowNull: false),
                    ]: []),
                    new Assert\Image(
                        maxSize: '1M',
                    ),
                ],
            ])
            ->add('timezone', TimezoneType::class, [
                'required' => false,
                'placeholder' => 'Select a timezone',
                'help' => 'Used to show whether restaurants are currently open.',
            ])
            ->add('googlePlacesEnabled', CheckboxType::class, [
                'required' => false,
                'label' => 'Google Places sync',
                'help' => 'Allow this hotel to link restaurants to Google and sync opening times.',
            ])
            ->add('latitude', NumberType::class, [
                'required' => false,
                'scale' => 6,
                'html5' => true,
                'attr' => ['step' => 'any', 'placeholder' => 'e.g. 53.479'],
                'help' => 'Marks the hotel on the Streetle map. Right-click the hotel in Google Maps to copy its coordinates.',
                'constraints' => [new Assert\Range(min: -90, max: 90)],
            ])
            ->add('longitude', NumberType::class, [
                'required' => false,
                'scale' => 6,
                'html5' => true,
                'attr' => ['step' => 'any', 'placeholder' => 'e.g. -2.245'],
                'constraints' => [new Assert\Range(min: -180, max: 180)],
            ]);
    }

    public static function validateLocation(Hotel $hotel, ExecutionContextInterface $context): void
    {
        if (($hotel->getLatitude() === null) === ($hotel->getLongitude() === null)) {
            return;
        }

        $context->buildViolation('Enter both a latitude and a longitude, or leave both empty.')
            ->atPath($hotel->getLatitude() === null ? 'latitude' : 'longitude')
            ->addViolation();
    }
}
