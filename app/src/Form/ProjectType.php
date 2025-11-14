<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Project;
use App\Enum\GoalType;
use App\Enum\Sector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de création/édition de projet marketing IA.
 *
 * Permet de définir les caractéristiques complètes du projet :
 * - Informations entreprise (nom, entreprise, secteur)
 * - Description et informations produit
 * - Objectifs marketing (type et détails SMART)
 * - Budget et timeline de campagne
 * - URL site web (pour analyse de marque)
 *
 * Intègre enrichissement IA via ProjectContextAnalyzerTool (Option B - Manuel).
 *
 * @extends AbstractType<Project>
 */
class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // === Section 1 : Informations Projet et Entreprise ===
            ->add('name', TextType::class, [
                'label' => 'Nom du projet',
                'help' => 'Nom descriptif de votre campagne marketing (ex: "Campagne Q4 2025 - Acquisition B2B")',
                'attr' => [
                    'placeholder' => 'Ex: Campagne marketing...',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom du projet est obligatoire',
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'help' => 'Nom de votre entreprise ou organisation (utilisé pour personnaliser les contenus générés)',
                'attr' => [
                    'placeholder' => 'Ex: TechCorp SAS',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom de l\'entreprise est obligatoire',
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('sector', ChoiceType::class, [
                'label' => 'Secteur d\'activité',
                'help' => 'Permet aux agents IA d\'adapter vocabulaire, benchmarks et recommandations',
                'choices' => Sector::choices(),
                'placeholder' => 'Choisissez un secteur',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le secteur est obligatoire',
                    ]),
                ],
            ])

            // === Section 2 : Description et Produit ===
            ->add('description', TextareaType::class, [
                'label' => 'Description du projet',
                'help' => 'Décrivez l\'objectif global de votre campagne et le contexte',
                'attr' => [
                    'placeholder' => 'Décrivez votre projet marketing...',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description du projet est obligatoire',
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('productInfo', TextareaType::class, [
                'label' => 'Informations sur le produit/service',
                'help' => 'Décrivez votre produit ou service : caractéristiques, avantages, public cible',
                'attr' => [
                    'placeholder' => 'Décrivez votre produit ou service...',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Les informations produit sont obligatoires',
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Les informations produit doivent contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ])

            // === Section 3 : Objectifs Marketing ===
            ->add('goalType', EnumType::class, [
                'class' => GoalType::class,
                'label' => 'Objectif marketing principal',
                'help' => 'Choisissez l\'objectif principal de votre campagne',
                'choice_label' => function (GoalType $goalType): string {
                    return match ($goalType) {
                        GoalType::AWARENESS => 'Notoriété - Faire connaître votre marque/produit',
                        GoalType::CONVERSION => 'Conversion - Générer des ventes ou leads',
                        GoalType::RETENTION => 'Fidélisation - Engager vos clients existants',
                    };
                },
                'placeholder' => 'Sélectionnez un objectif',
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'L\'objectif marketing est obligatoire',
                    ]),
                ],
            ])
            ->add('detailedObjectives', TextareaType::class, [
                'label' => 'Objectifs marketing détaillés',
                'help' => 'Décrivez précisément vos objectifs SMART : Spécifiques, Mesurables, Atteignables, Réalistes, Temporels',
                'attr' => [
                    'placeholder' => 'Ex: "Générer 100 leads qualifiés/mois, réduire CAC de 20%, augmenter trafic site de 50%"',
                    'rows' => 5,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Les objectifs détaillés sont obligatoires',
                    ]),
                    new Assert\Length([
                        'min' => 20,
                        'minMessage' => 'Les objectifs doivent contenir au moins {{ limit }} caractères pour être précis',
                    ]),
                ],
            ])

            // === Section 4 : Budget et Timeline ===
            ->add('budget', MoneyType::class, [
                'label' => 'Budget total (€)',
                'help' => 'Budget total alloué à cette campagne marketing (minimum 100€)',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => '5000.00',
                    'min' => '100',
                    'step' => '0.01',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le budget est obligatoire',
                    ]),
                    new Assert\Positive([
                        'message' => 'Le budget doit être supérieur à zéro',
                    ]),
                    new Assert\Range([
                        'min' => 100,
                        'max' => 10000000,
                        'notInRangeMessage' => 'Le budget doit être entre {{ min }}€ et {{ max }}€',
                    ]),
                ],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début de campagne',
                'help' => 'Date à laquelle la campagne marketing commencera',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'min' => (new \DateTimeImmutable())->format('Y-m-d'),
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de début est obligatoire',
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de début ne peut pas être dans le passé',
                    ]),
                ],
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin de campagne',
                'help' => 'Date à laquelle la campagne marketing se terminera (durée recommandée : 30-90 jours)',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'min' => (new \DateTimeImmutable())->format('Y-m-d'),
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de fin est obligatoire',
                    ]),
                    new Assert\GreaterThan([
                        'propertyPath' => 'parent.all[startDate].data',
                        'message' => 'La date de fin doit être postérieure à la date de début',
                    ]),
                ],
            ])

            // === Section 5 : Informations Complémentaires ===
            ->add('websiteUrl', UrlType::class, [
                'label' => 'URL du site web (optionnel)',
                'help' => 'URL de votre site web pour analyse automatique de l\'identité visuelle de marque',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://www.example.com',
                ],
                'constraints' => [
                    new Assert\Url([
                        'message' => 'L\'URL doit être valide (ex: https://example.com)',
                    ]),
                ],
            ])
            ->add('selectedAssetTypes', ChoiceType::class, [
                'label' => 'Types d\'assets à générer',
                'help' => 'Sélectionnez les canaux marketing pour vos contenus. Si aucun n\'est sélectionné, tous seront générés.',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Publication LinkedIn' => 'linkedin_post',
                    'Publicité Google Ads' => 'google_ads',
                    'Publication Facebook' => 'facebook_post',
                    'Publication Instagram' => 'instagram_post',
                    'Email marketing' => 'mail',
                    'Publicité Bing Ads' => 'bing_ads',
                    'Bannière IAB' => 'iab',
                    'Article de blog' => 'article',
                ],
            ])

            // === Boutons Submit ===
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer le projet',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg',
                ],
            ])
            ->add('analyze', SubmitType::class, [
                'label' => 'Analyser et améliorer avec l\'IA',
                'attr' => [
                    'class' => 'btn btn-secondary',
                    'formnovalidate' => 'formnovalidate',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'csrf_protection' => false,
        ]);
    }
}
