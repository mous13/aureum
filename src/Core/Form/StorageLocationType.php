<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Enum\StorageLocationType as LocationTypeEnum;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StorageLocationType extends AbstractType
{
    /**
     * @return array<string, LocationTypeEnum>
     */
    public static function typeChoices(): array
    {
        $choices = [];
        foreach (LocationTypeEnum::cases() as $case) {
            $choices[$case->getLabel()] = $case;
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => StorageLocation::class]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['placeholder' => 'e.g. Store Room 1'],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => self::typeChoices(),
                'help' => 'Bulk stores are counted and forecast. Working locations are destinations and are not forecast.',
            ])
            ->add('position', IntegerType::class, ['required' => false])
            ->add('active', CheckboxType::class, ['required' => false]);
    }
}
