<?php

declare(strict_types=1);

namespace App\Service\Marketing;

use Psr\Log\LoggerInterface;

/**
 * Service de stockage des images générées pour les assets marketing.
 *
 * Responsabilités :
 * - Sauvegarder les images depuis base64 vers filesystem
 * - Générer des noms de fichiers uniques et sécurisés
 * - Supprimer les images orphelines
 * - Retourner les URLs publiques pour affichage
 *
 * Architecture production-ready :
 * - Stockage : /public/uploads/marketing/assets/{assetId}_{hash}.{ext}
 * - URL publique : /uploads/marketing/assets/{assetId}_{hash}.{ext}
 * - Permissions : www-data:www-data, 755 (répertoires), 644 (fichiers)
 * - Backup : séparé de la BDD
 *
 * @since feature/image-generation-production
 */
final readonly class AssetImageStorageService
{
    /**
     * Répertoire de stockage des images (relatif à /public).
     */
    private const UPLOAD_DIR = 'uploads/marketing/assets';

    /**
     * Extensions autorisées pour les images.
     *
     * @var array<string>
     */
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    public function __construct(
        private string $publicDir,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Stocke une image depuis base64 vers le filesystem.
     *
     * @param string $base64Data  Données image en base64 (avec ou sans préfixe data:image/...)
     * @param int    $assetId     ID de l'asset propriétaire
     * @param string $format      Format de l'image (png, jpg, webp)
     * @param string $description Description de l'image (pour alt text)
     *
     * @return array{path: string, url: string, size_bytes: int} Informations de stockage
     *
     * @throws \RuntimeException Si le stockage échoue
     */
    public function store(string $base64Data, int $assetId, string $format = 'png', string $description = ''): array
    {
        // Valider le format
        if (! in_array(strtolower($format), self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(sprintf('Format "%s" non autorisé. Formats acceptés : %s', $format, implode(', ', self::ALLOWED_EXTENSIONS)));
        }

        // Nettoyer le base64 (retirer préfixe data:image/... si présent)
        $cleanBase64 = $this->cleanBase64($base64Data);

        // Décoder le base64
        $imageContent = base64_decode($cleanBase64, true);
        if (false === $imageContent) {
            throw new \RuntimeException('Échec du décodage base64 de l\'image');
        }

        // Créer le répertoire si nécessaire
        $uploadPath = $this->getUploadPath();
        if (! is_dir($uploadPath)) {
            if (! mkdir($uploadPath, 0755, true)) {
                throw new \RuntimeException(sprintf('Impossible de créer le répertoire "%s"', $uploadPath));
            }
        }

        // Générer nom de fichier unique : {assetId}_{hash}.{ext}
        $hash = substr(md5($imageContent), 0, 8);
        $filename = sprintf('%d_%s.%s', $assetId, $hash, $format);
        $filePath = $uploadPath.'/'.$filename;

        // Sauvegarder le fichier
        $bytesWritten = file_put_contents($filePath, $imageContent);
        if (false === $bytesWritten) {
            throw new \RuntimeException(sprintf('Échec de l\'écriture du fichier "%s"', $filePath));
        }

        // Appliquer les permissions correctes
        chmod($filePath, 0644);

        $url = '/'.self::UPLOAD_DIR.'/'.$filename;

        $this->logger->info('Image asset sauvegardée sur filesystem', [
            'asset_id' => $assetId,
            'filename' => $filename,
            'url' => $url,
            'size_bytes' => $bytesWritten,
            'format' => $format,
        ]);

        return [
            'path' => $filePath,
            'url' => $url,
            'size_bytes' => $bytesWritten,
        ];
    }

    /**
     * Supprime une image depuis le filesystem.
     *
     * @param string $url URL publique de l'image (/uploads/marketing/assets/...)
     *
     * @return bool True si supprimé, false si fichier n'existe pas
     */
    public function delete(string $url): bool
    {
        $filePath = $this->urlToFilePath($url);

        if (! file_exists($filePath)) {
            $this->logger->warning('Tentative de suppression d\'une image inexistante', [
                'url' => $url,
                'path' => $filePath,
            ]);

            return false;
        }

        $deleted = unlink($filePath);

        if ($deleted) {
            $this->logger->info('Image asset supprimée du filesystem', [
                'url' => $url,
                'path' => $filePath,
            ]);
        }

        return $deleted;
    }

    /**
     * Vérifie si une image existe sur le filesystem.
     *
     * @param string $url URL publique de l'image
     */
    public function exists(string $url): bool
    {
        return file_exists($this->urlToFilePath($url));
    }

    /**
     * Retourne le chemin absolu du répertoire d'upload.
     */
    private function getUploadPath(): string
    {
        return $this->publicDir.'/'.self::UPLOAD_DIR;
    }

    /**
     * Convertit une URL publique en chemin absolu filesystem.
     *
     * Exemple : /uploads/marketing/assets/11_abc123.png → /var/www/html/public/uploads/marketing/assets/11_abc123.png
     */
    private function urlToFilePath(string $url): string
    {
        // Retirer le préfixe '/' si présent
        $relativePath = ltrim($url, '/');

        return $this->publicDir.'/'.$relativePath;
    }

    /**
     * Nettoie une chaîne base64 en retirant le préfixe data:image/... si présent.
     *
     * Exemple : data:image/png;base64,iVBORw0... → iVBORw0...
     */
    private function cleanBase64(string $base64Data): string
    {
        // Si la chaîne commence par "data:", retirer tout jusqu'à la virgule
        if (str_starts_with($base64Data, 'data:')) {
            $parts = explode(',', $base64Data, 2);

            return $parts[1] ?? $base64Data;
        }

        return $base64Data;
    }
}
