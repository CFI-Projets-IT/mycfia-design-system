<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de génération de stratégie marketing par IA.
 *
 * Permet de paramétrer la génération automatique de la stratégie
 * par l'agent StrategyAnalystAgent du Marketing AI Bundle.
 *
 * @extends AbstractType<mixed>
 */
class StrategyGenerationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('includeCompetitorAnalysis', CheckboxType::class, [
                'label' => 'Inclure une analyse concurrentielle',
                'help' => 'L\'IA analysera vos concurrents et identifiera des opportunités de différenciation',
                'required' => false,
                'data' => true,
            ])
            ->add('focusChannels', ChoiceType::class, [
                'label' => 'Canaux marketing à privilégier',
                'help' => 'Sélectionnez les canaux que vous souhaitez privilégier (optionnel)',
                'required' => false,
                'multiple' => true,
                'expanded' => false,
                'choices' => [
                    'Réseaux sociaux (Facebook, Instagram, LinkedIn)' => 'social',
                    'Publicité en ligne (Google Ads, Bing Ads)' => 'search',
                    'Display / Bannières publicitaires' => 'display',
                    'Email marketing' => 'email',
                    'Contenu / SEO / Articles' => 'content',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\Count([
                        'max' => 5,
                        'maxMessage' => 'Vous ne pouvez sélectionner que {{ limit }} canaux maximum',
                    ]),
                ],
            ])
            ->add('additionalContext', TextareaType::class, [
                'label' => 'Contexte additionnel (optionnel)',
                'help' => 'Contraintes spécifiques, préférences stratégiques, éléments de marque à respecter...',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Privilégier les canaux digitaux, ton de communication décalé, éviter les comparaisons directes...',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Le contexte additionnel ne peut pas dépasser {{ limit }} caractères',
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
