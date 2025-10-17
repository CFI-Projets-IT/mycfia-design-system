<?php

declare(strict_types=1);

namespace App\Service\Ai;

use Symfony\AI\Agent\Output;
use Symfony\AI\Agent\OutputProcessorInterface;
use Symfony\AI\Platform\Result\StreamResult;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * OutputProcessor personnalisé pour capturer model + token_usage depuis Mistral.
 *
 * Complète TokenOutputProcessor natif de Symfony AI en capturant le champ "model"
 * qui n'est pas extrait par défaut.
 *
 * Responsabilités :
 * - Capturer le nom du modèle Mistral utilisé (ex: mistral-large-latest)
 * - Fonctionne UNIQUEMENT en mode non-streaming (comme TokenOutputProcessor)
 * - Priorité 200 pour s'exécuter APRÈS TokenOutputProcessor (priorité 100)
 */
final readonly class MistralMetadataProcessor implements OutputProcessorInterface
{
    public function processOutput(Output $output): void
    {
        // Ignorer streaming (comme TokenOutputProcessor)
        if ($output->getResult() instanceof StreamResult) {
            return;
        }

        $rawResponse = $output->getResult()->getRawResult()?->getObject();
        if (! $rawResponse instanceof ResponseInterface) {
            return;
        }

        $content = $rawResponse->toArray(false);

        // Extraire le model si présent dans la réponse Mistral
        if (\array_key_exists('model', $content)) {
            $metadata = $output->getResult()->getMetadata();
            $metadata->add('model', $content['model']);
        }
    }
}
