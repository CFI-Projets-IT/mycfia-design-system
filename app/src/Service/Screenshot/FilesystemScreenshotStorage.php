<?php

declare(strict_types=1);

namespace App\Service\Screenshot;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Stockage screenshots sur filesystem local.
 * Production-ready avec gestion erreurs et logging.
 * Migration future vers S3 : créer S3ScreenshotStorage implémentant ScreenshotStorageInterface.
 */
final readonly class FilesystemScreenshotStorage implements ScreenshotStorageInterface
{
    private const UPLOAD_DIR = '/uploads/screenshots';
    private const MAX_SIZE_MB = 5;

    public function __construct(
        private string $publicDir,
        private RequestStack $requestStack,
        private Filesystem $filesystem,
        private LoggerInterface $logger,
    ) {
    }

    public function store(string $base64Image, string $projectId): string
    {
        try {
            // Validation format base64
            if (! str_starts_with($base64Image, 'data:image/')) {
                throw new \InvalidArgumentException('Format base64 invalide (attendu : data:image/...)');
            }

            // Extraction données base64
            [$metadata, $data] = explode(',', $base64Image, 2);
            $decodedData = base64_decode($data, true);

            if (false === $decodedData) {
                throw new \RuntimeException('Échec décodage base64');
            }

            // Validation taille
            $sizeInMb = strlen($decodedData) / 1024 / 1024;
            if ($sizeInMb > self::MAX_SIZE_MB) {
                throw new \RuntimeException(sprintf('Screenshot trop volumineux : %.2f MB (max %d MB)', $sizeInMb, self::MAX_SIZE_MB));
            }

            // Détection extension depuis metadata
            preg_match('/data:image\/(\w+);/', $metadata, $matches);
            $extension = $matches[1] ?? 'png';

            // Génération nom fichier unique
            $filename = sprintf(
                'project-%s-%s.%s',
                $projectId,
                date('Ymd-His'),
                $extension
            );

            // Création répertoire si nécessaire
            $uploadPath = $this->publicDir.self::UPLOAD_DIR;
            if (! $this->filesystem->exists($uploadPath)) {
                $this->filesystem->mkdir($uploadPath, 0755);
            }

            // Écriture fichier
            $filepath = $uploadPath.'/'.$filename;
            $this->filesystem->dumpFile($filepath, $decodedData);

            // URL relative
            $relativeUrl = self::UPLOAD_DIR.'/'.$filename;

            $this->logger->info('Screenshot stocké avec succès', [
                'project_id' => $projectId,
                'filename' => $filename,
                'size_mb' => round($sizeInMb, 2),
                'url' => $relativeUrl,
            ]);

            return $relativeUrl;
        } catch (\Throwable $e) {
            $this->logger->error('Échec stockage screenshot', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException(sprintf('Échec stockage screenshot : %s', $e->getMessage()), 0, $e);
        }
    }

    public function delete(string $url): bool
    {
        try {
            $filepath = $this->publicDir.$url;

            if (! $this->filesystem->exists($filepath)) {
                $this->logger->warning('Screenshot introuvable pour suppression', ['url' => $url]);

                return false;
            }

            $this->filesystem->remove($filepath);

            $this->logger->info('Screenshot supprimé', ['url' => $url]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Échec suppression screenshot', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function exists(string $url): bool
    {
        $filepath = $this->publicDir.$url;

        return $this->filesystem->exists($filepath);
    }

    public function getUrl(string $relativePath): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            // Fallback CLI ou contexte sans requête
            return $relativePath;
        }

        // URL complète avec domaine
        return $request->getSchemeAndHttpHost().$relativePath;
    }
}
