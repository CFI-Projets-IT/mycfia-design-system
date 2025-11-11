<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Persona;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de génération de stratégie marketing.
 *
 * Permet de sélectionner :
 * - Le persona principal à cibler
 * - Les canaux marketing à utiliser (1 à 8 canaux)
 * - Une liste optionnelle de concurrents pour analyse concurrentielle
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
            ->add('persona', EntityType::class, [
                'class' => Persona::class,
                'label' => 'Persona ciblé',
                'help' => 'Sélectionnez le persona principal pour cette stratégie',
                'choices' => $personas,
                'choice_label' => function (Persona $persona): string {
                    return $persona->getName().' - '.$persona->getAge().' ans, '.$persona->getJob();
                },
                'placeholder' => 'Choisissez un persona',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le persona est obligatoire']),
                ],
            ])
            ->add('channels', ChoiceType::class, [
                'label' => 'Canaux marketing',
                'help' => 'Sélectionnez les canaux que vous souhaitez utiliser (1 à 8)',
                'choices' => [
                    'Google Ads' => 'google_ads',
                    'LinkedIn Ads' => 'linkedin_ads',
                    'Facebook Ads' => 'facebook_ads',
                    'Instagram Ads' => 'instagram_ads',
                    'Email Marketing' => 'email',
                    'Bing Ads' => 'bing_ads',
                    'Display IAB' => 'display_iab',
                    'Content Marketing (Articles SEO)' => 'content_marketing',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'label_attr' => [
                    'class' => 'form-label fw-semibold',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Sélectionnez au moins un canal']),
                    new Assert\Count([
                        'min' => 1,
                        'max' => 8,
                        'minMessage' => 'Sélectionnez au moins {{ limit }} canal',
                        'maxMessage' => 'Vous ne pouvez pas sélectionner plus de {{ limit }} canaux',
                    ]),
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
