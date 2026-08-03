<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Form;

use Citadel\Aureum\Core\Entity\Announcement;
use Citadel\Aureum\Core\Entity\Enum\AnnouncementSeverity;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnnouncementType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $modules = [];
        foreach (Module::cases() as $module) {
            $modules[$module->getLabel()] = $module;
        }

        $severities = [];
        foreach (AnnouncementSeverity::cases() as $severity) {
            $severities[$severity->getLabel()] = $severity;
        }

        $builder
            ->add('title', TextType::class, [
                'attr' => ['maxlength' => 255],
            ])
            ->add('message', TextareaType::class, [
                'attr' => ['rows' => 4],
            ])
            ->add('module', ChoiceType::class, [
                'choices' => $modules,
                'help' => 'The module page this announcement appears on.',
            ])
            ->add('severity', ChoiceType::class, [
                'choices' => $severities,
            ])
        ;
    }
}
