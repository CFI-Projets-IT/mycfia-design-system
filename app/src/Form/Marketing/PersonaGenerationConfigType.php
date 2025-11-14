<?php

declare(strict_types=1);

namespace App\Form\Marketing;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de configuration pour la génération de personas.
 *
 * Permet de définir :
 * - Le nombre de personas à générer (1-10)
 * - Le seuil de qualité minimum (0-100)
 *
 * @extends AbstractType<array<string, mixed>>
 */
class PersonaGenerationConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('count', ChoiceType::class, [
                'label' => 'Nombre de personas',
                'choices' => [
                    '1 persona' => 1,
                    '3 personas (recommandé)' => 3,
                    '5 personas' => 5,
                    '10 personas' => 10,
                ],
                'data' => 3, // Valeur par défaut
                'help' => 'Plus de personas = meilleure diversification des profils',
                'attr' => [
                    'class' => 'form-select',
                    'data-persona-count-target' => 'input',
                ],
            ])
            ->add('minQualityScore', ChoiceType::class, [
                'label' => 'Seuil de qualité',
                'choices' => [
                    'Standard (60%)' => 60,
                    'Strict (70%)' => 70,
                    'Très strict (80%)' => 80,
                ],
                'data' => 70, // Valeur par défaut
                'required' => false,
                'help' => 'Score minimum accepté pour les personas générés (0-100)',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'persona_generation_config',
        ]);
    }
}
