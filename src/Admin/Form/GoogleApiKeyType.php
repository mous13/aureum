<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class GoogleApiKeyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('apiKey', PasswordType::class, [
            'label' => 'Google Places API key',
            'attr' => [
                'autocomplete' => 'off',
                'placeholder' => 'Paste the API key',
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(min: 20, max: 512),
            ],
        ]);
    }
}
