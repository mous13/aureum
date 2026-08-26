<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form;

use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\HotelRole;
use Citadel\Aureum\Core\Entity\Sop;
use Citadel\Aureum\Core\Entity\SopCategory;
use Doctrine\ORM\EntityRepository;
use Forumify\Core\Form\RichTextEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SopType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sop::class,
            'is_published' => false,
        ]);

        $resolver->setRequired('hotel');
        $resolver->setAllowedTypes('hotel', Hotel::class);
        $resolver->setAllowedTypes('is_published', 'bool');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $hotel = $options['hotel'];

        $builder
            ->add('title', TextType::class, [
                'attr' => ['placeholder' => 'e.g. VIP Arrival Procedure'],
            ])
            ->add('category', EntityType::class, [
                'class' => SopCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Uncategorised',
                'required' => false,
                'query_builder' => static fn (EntityRepository $repo) => $repo->createQueryBuilder('c')
                    ->where('c.hotel = :hotel')
                    ->setParameter('hotel', $hotel)
                    ->orderBy('c.name', 'ASC'),
            ])
            ->add('body', RichTextEditorType::class, [
                'label' => 'Procedure',
            ])
            ->add('recheckMonths', IntegerType::class, [
                'label' => 'Recheck every (months)',
                'required' => false,
                'help' => 'Sign-offs lapse after this many months and staff are asked to confirm again. Leave blank to require a single sign-off only.',
                'attr' => ['min' => 1, 'max' => 60],
            ])
            ->add('audience', EntityType::class, [
                'class' => HotelRole::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'by_reference' => false,
                'help' => 'Which roles must sign this procedure. Leave empty to apply to everyone.',
                'query_builder' => static fn (EntityRepository $repo) => $repo->createQueryBuilder('r')
                    ->where('r.hotel = :hotel')
                    ->setParameter('hotel', $hotel)
                    ->orderBy('r.name', 'ASC'),
            ]);

        if ($options['is_published']) {
            $builder->add('changeKind', ChoiceType::class, [
                'label' => 'This change is',
                'mapped' => false,
                'expanded' => true,
                'choices' => [
                    'A minor correction, existing sign-offs stand' => 'minor',
                    'A new version, everyone must sign off again' => 'new_version',
                ],
                'data' => 'minor',
            ]);
        }
    }
}
