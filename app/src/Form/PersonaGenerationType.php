<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de génération de personas marketing par IA.
 *
 * Permet de paramétrer la génération automatique de personas
 * par l'agent PersonaGeneratorAgent du Marketing AI Bundle.
 *
 * @extends AbstractType<mixed>
 */
class PersonaGenerationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numberOfPersonas', IntegerType::class, [
                'label' => 'Nombre de personas à générer',
                'help' => 'Recommandé : 2-3 personas pour une campagne ciblée',
                'data' => 3,
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Le nombre de personas est obligatoire',
                    ),
                    new Assert\Range(
                        min: 1,
                        max: 5,
                        notInRangeMessage: 'Vous pouvez générer entre {{ min }} et {{ max }} personas',
                    ),
                ],
            ])
            ->add('additionalContext', TextareaType::class, [
                'label' => 'Contexte additionnel (optionnel)',
                'help' => 'Informations supplémentaires pour affiner les personas (caractéristiques spécifiques, secteur d\'activité, contraintes...)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Public principalement féminin, 25-45 ans, urbain, sensible à l\'écologie...',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 1000,
                        maxMessage: 'Le contexte additionnel ne peut pas dépasser {{ limit }} caractères',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pas de data_class car ce formulaire ne mappe pas une entité
            // Il sert uniquement de DTO pour les paramètres de génération
        ]);
    }
}
