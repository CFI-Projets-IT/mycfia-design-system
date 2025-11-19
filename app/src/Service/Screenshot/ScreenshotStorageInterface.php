<?php

declare(strict_types=1);

namespace App\Service\Screenshot;

/**
 * Interface pour le stockage des screenshots de sites web.
 * Permet abstraction filesystem/S3 pour scalabilité production.
 */
interface ScreenshotStorageInterface
{
    /**
     * Stocke un screenshot base64 et retourne l'URL d'accès.
     *
     * @param string $base64Image Screenshot en base64 (format : data:image/png;base64,...)
     * @param string $projectId   ID du projet (pour nommage unique)
     *
     * @return string URL publique d'accès au screenshot
     *
     * @throws \RuntimeException Si échec upload ou stockage
     */
    public function store(string $base64Image, string $projectId): string;

    /**
     * Supprime un screenshot depuis son URL.
     *
     * @param string $url URL du screenshot à supprimer
     *
     * @return bool True si supprimé, false si introuvable
     */
    public function delete(string $url): bool;

    /**
     * Vérifie si un screenshot existe.
     *
     * @param string $url URL du screenshot
     *
     * @return bool True si existe
     */
    public function exists(string $url): bool;

    /**
     * Retourne l'URL complète d'un screenshot.
     *
     * @param string $relativePath Chemin relatif du screenshot
     *
     * @return string URL complète
     */
    public function getUrl(string $relativePath): string;
}
