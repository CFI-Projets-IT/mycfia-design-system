<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Persona;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de génération de stratégie marketing.
 *
 * Permet de sélectionner :
 * - Un ou plusieurs personas à cibler (sélection multiple)
 * - Une liste optionnelle de concurrents pour analyse concurrentielle
 *
 * Les canaux marketing sont désormais sélectionnés à la création du projet
 * et récupérés depuis $project->getSelectedChannels().
 *
 * Utilisé dans StrategyController.new() pour dispatcher vers StrategyAnalystAgent.
 *
 * @extends AbstractType<null>
 */
class StrategyGenerationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<Persona> $personas */
        $personas = $options['personas'];

        $builder
            ->add('personas', EntityType::class, [
                'class' => Persona::class,
                'label' => 'Personas ciblés',
                'help' => 'Sélectionnez un ou plusieurs personas pour cette stratégie (maintenez Ctrl/Cmd pour sélection multiple)',
                'choices' => $personas,
                'choice_label' => function (Persona $persona): string {
                    return $persona->getName().' - '.$persona->getAge().' ans, '.$persona->getJob();
                },
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'label_attr' => [
                    'class' => 'form-label fw-semibold',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Sélectionnez au moins un persona'),
                    new Assert\Count(
                        min: 1,
                        minMessage: 'Sélectionnez au moins {{ limit }} persona',
                    ),
                ],
            ])
            ->add('competitors', TextareaType::class, [
                'label' => 'Concurrents (optionnel)',
                'help' => 'Liste de concurrents séparés par des virgules pour analyse concurrentielle IA',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Concurrent 1, Concurrent 2, Concurrent 3...',
                    'rows' => 3,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'personas' => [],
        ]);

        $resolver->setAllowedTypes('personas', 'array');
    }
}
