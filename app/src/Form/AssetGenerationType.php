<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de génération d'assets marketing par IA.
 *
 * Permet de paramétrer la génération automatique d'assets multi-canal
 * par les AssetBuilders du Marketing AI Bundle.
 *
 * @extends AbstractType<mixed>
 */
class AssetGenerationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('assetTypes', ChoiceType::class, [
                'label' => 'Types d\'assets à générer',
                'help' => 'Sélectionnez les types de contenu marketing à générer automatiquement',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Google Ads (publicité search)' => 'google_ads',
                    'LinkedIn Post (réseau professionnel)' => 'linkedin_post',
                    'Facebook Post (réseau social)' => 'facebook_post',
                    'Instagram Post (réseau social visuel)' => 'instagram_post',
                    'Email marketing' => 'mail',
                    'Bing Ads (publicité search alternative)' => 'bing_ads',
                    'IAB Banner (bannière publicitaire)' => 'iab_banner',
                    'Article SEO (contenu blog)' => 'article_seo',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Vous devez sélectionner au moins un type d\'asset',
                    ]),
                    new Assert\Count([
                        'min' => 1,
                        'max' => 8,
                        'minMessage' => 'Vous devez sélectionner au moins {{ limit }} type d\'asset',
                        'maxMessage' => 'Vous ne pouvez sélectionner que {{ limit }} types maximum',
                    ]),
                ],
            ])
            ->add('numberOfVariations', IntegerType::class, [
                'label' => 'Nombre de variations par asset',
                'help' => 'Générer plusieurs versions pour chaque asset (A/B testing)',
                'data' => 1,
                'attr' => [
                    'min' => 1,
                    'max' => 3,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nombre de variations est obligatoire',
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 3,
                        'notInRangeMessage' => 'Vous pouvez générer entre {{ min }} et {{ max }} variations',
                    ]),
                ],
            ])
            ->add('toneOfVoice', ChoiceType::class, [
                'label' => 'Ton de communication',
                'help' => 'Définissez le style de communication pour vos assets',
                'placeholder' => 'Ton adapté automatiquement',
                'required' => false,
                'choices' => [
                    'Professionnel et formel' => 'professional',
                    'Amical et accessible' => 'friendly',
                    'Dynamique et motivant' => 'energetic',
                    'Expert et technique' => 'expert',
                    'Humoristique et décalé' => 'humorous',
                    'Élégant et premium' => 'elegant',
                ],
            ])
            ->add('additionalContext', TextareaType::class, [
                'label' => 'Instructions spécifiques (optionnel)',
                'help' => 'Directives particulières, mots-clés à inclure, call-to-action spécifique...',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Inclure le hashtag #EcoFriendly, mettre en avant la livraison gratuite, éviter les superlatifs...',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Les instructions ne peuvent pas dépasser {{ limit }} caractères',
                    ]),
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
