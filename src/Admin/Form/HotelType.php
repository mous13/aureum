<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Form;

use Citadel\Aureum\Core\Entity\Hotel;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class HotelType extends AbstractType
{
    public function __construct (
        private readonly Packages $packages
    ){
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Hotel::class,
                'image_required' => false,
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $imagePreview = empty($options['data']) ? null : $options['data']->getLogo();

        $builder
            ->add('name', TextType::class)
            ->add('code', TextType::class, [
                'help' => 'This can be your property code or a made up code between 5-7 letters'
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
                ]
            ]);
    }
}
