<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets SMS Marketing.
 *
 * Formate les données des SMS marketing conformes CNIL/RGPD
 * (body, encoding, segments, conformité, placeholders, etc.)
 * pour affichage dans les templates.
 *
 * Structure SmsAssetDTO (marketing-ai-bundle v3.38.0) :
 * - body : Corps SMS complet avec placeholders + footer STOP
 * - encoding : 'gsm' ou 'unicode'
 * - segment_count : Nombre de segments (1-4)
 * - character_count : Caractères utilisés
 * - character_budget : Budget disponible
 * - personalization_fields : Placeholders [{PRENOM}, {LIEN}, etc.]
 * - cta : Call-to-action principal
 * - urgency_trigger : Type d'urgence
 * - footer : Mention légale STOP
 * - send_window : Horaires légaux CNIL
 * - cnil_compliant : Conformité validée (bool)
 * - variations : 2-3 variations alternatives
 * - optimization_suggestions : Suggestions d'optimisation
 */
final readonly class SmsAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        return 'sms' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset SMS #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'sms',
            'icon' => 'chat-text',
            'label' => 'SMS',
            'main_content' => $this->extractMainContent($content),
            'variations' => $this->getVariations($asset),
        ];
    }

    public function getVariations(Asset $asset): array
    {
        $variations = $asset->getVariationsArray();

        if (null === $variations || [] === $variations) {
            return [];
        }

        $formatted = [];
        foreach ($variations as $variation) {
            $formatted[] = $this->extractMainContent($variation);
        }

        return $formatted;
    }

    /**
     * Extrait et formate le contenu principal d'un SMS marketing.
     *
     * Gère les 13 propriétés du SmsAssetDTO selon le bundle v3.38.0.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Body (corps SMS complet avec footer STOP)
        if (isset($data['body']) && is_string($data['body'])) {
            $content['body'] = $data['body'];
        }

        // Encoding (gsm ou unicode)
        if (isset($data['encoding']) && is_string($data['encoding'])) {
            $content['encoding'] = $data['encoding'];
        }

        // Segment count (nombre de segments)
        if (isset($data['segment_count']) && is_int($data['segment_count'])) {
            $content['segment_count'] = $data['segment_count'];
        }

        // Character count (caractères utilisés)
        if (isset($data['character_count']) && is_int($data['character_count'])) {
            $content['character_count'] = $data['character_count'];
        }

        // Character budget (budget disponible)
        if (isset($data['character_budget']) && is_int($data['character_budget'])) {
            $content['character_budget'] = $data['character_budget'];
        }

        // Personalization fields (placeholders)
        if (isset($data['personalization_fields']) && is_array($data['personalization_fields'])) {
            $content['personalization_fields'] = array_values(array_filter($data['personalization_fields'], 'is_string'));
        }

        // CTA (call-to-action principal)
        if (isset($data['cta']) && is_string($data['cta'])) {
            $content['cta'] = $data['cta'];
        }

        // Urgency trigger (type d'urgence)
        if (isset($data['urgency_trigger']) && is_string($data['urgency_trigger'])) {
            $content['urgency_trigger'] = $data['urgency_trigger'];
        }

        // Footer (mention légale STOP)
        if (isset($data['footer']) && is_string($data['footer'])) {
            $content['footer'] = $data['footer'];
        }

        // Send window (horaires légaux CNIL)
        if (isset($data['send_window']) && is_string($data['send_window'])) {
            $content['send_window'] = $data['send_window'];
        }

        // CNIL compliant (conformité validée)
        if (isset($data['cnil_compliant']) && is_bool($data['cnil_compliant'])) {
            $content['cnil_compliant'] = $data['cnil_compliant'];
        }

        // Optimization suggestions (suggestions d'optimisation)
        if (isset($data['optimization_suggestions']) && is_array($data['optimization_suggestions'])) {
            $content['optimization_suggestions'] = array_values(array_filter($data['optimization_suggestions'], 'is_string'));
        }

        return $content;
    }
}
