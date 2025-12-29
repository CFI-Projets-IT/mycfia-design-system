<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension Twig pour gérer le JSON (décodage/encodage).
 */
final class JsonExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', $this->jsonDecode(...)),
        ];
    }

    /**
     * Décode une chaîne JSON en array PHP.
     *
     * @return mixed|null
     */
    public function jsonDecode(string $json, bool $associative = true): mixed
    {
        try {
            return json_decode($json, $associative, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }
}
