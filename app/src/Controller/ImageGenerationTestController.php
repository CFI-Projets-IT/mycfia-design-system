<?php

declare(strict_types=1);

namespace App\Controller;

use Gorillias\MarketingBundle\AssetBuilder\ArticleAssetBuilder;
use Gorillias\MarketingBundle\AssetBuilder\FacebookPostAssetBuilder;
use Gorillias\MarketingBundle\AssetBuilder\IabAssetBuilder;
use Gorillias\MarketingBundle\AssetBuilder\InstagramPostAssetBuilder;
use Gorillias\MarketingBundle\AssetBuilder\LinkedinPostAssetBuilder;
use Gorillias\MarketingBundle\Service\ImageGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller de test pour la fonctionnalité de génération d'images IA.
 * Tests des 5 AssetBuilders compatibles avec la génération d'images.
 */
#[Route('/test/image-generation', name: 'test_image_generation_')]
final class ImageGenerationTestController extends AbstractController
{
    private const STYLES = [
        'flat_illustration',
        'realistic',
        'minimalist',
        'corporate',
        'abstract',
    ];

    /**
     * Injection du service ImageGenerationService pour forcer sa compilation dans le conteneur DI.
     * Cela évite que Symfony l'optimise/inline puisqu'il est utilisé de manière optionnelle (@?) dans les AssetBuilders.
     */
    public function __construct(
        private readonly ImageGenerationService $imageGenerationService,
    ) {
    }

    /**
     * Page d'accueil du module de test.
     */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('image_generation_test/index.html.twig', [
            'builders' => [
                'instagram' => 'Instagram Post (1:1, 1080x1080)',
                'facebook' => 'Facebook Post (1.91:1, 1200x628)',
                'linkedin' => 'LinkedIn Post (1.91:1, 1200x627)',
                'article' => 'Article (16:9, 1200x675)',
                'iab' => 'IAB (formats variables)',
            ],
            'styles' => self::STYLES,
        ]);
    }

    /**
     * Test manuel du logger - Vérifier que le canal marketing.service.image_generation fonctionne.
     */
    #[Route('/test-logger', name: 'test_logger')]
    public function testLogger(
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(service: 'monolog.logger.marketing.service.image_generation')]
        \Psr\Log\LoggerInterface $logger
    ): JsonResponse {
        $logger->info('TEST MANUEL - Canal image_generation fonctionne !', [
            'timestamp' => time(),
            'test_id' => uniqid('test_'),
        ]);

        return $this->json([
            'success' => true,
            'message' => 'Log écrit, vérifiez var/log/marketing/services/image_generation.log',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Test génération Instagram Post.
     */
    #[Route('/instagram/{style}', name: 'instagram', defaults: ['style' => 'flat_illustration'])]
    public function testInstagram(
        string $style,
        InstagramPostAssetBuilder $instagramBuilder
    ): JsonResponse {
        return $this->generateImageTest(
            $instagramBuilder,
            $style,
            'Instagram Post',
            '1:1 (1080x1080)'
        );
    }

    /**
     * Test génération Facebook Post.
     */
    #[Route('/facebook/{style}', name: 'facebook', defaults: ['style' => 'flat_illustration'])]
    public function testFacebook(
        string $style,
        FacebookPostAssetBuilder $facebookBuilder
    ): JsonResponse {
        return $this->generateImageTest(
            $facebookBuilder,
            $style,
            'Facebook Post',
            '1.91:1 (1200x628)'
        );
    }

    /**
     * Test génération LinkedIn Post.
     */
    #[Route('/linkedin/{style}', name: 'linkedin', defaults: ['style' => 'flat_illustration'])]
    public function testLinkedin(
        string $style,
        LinkedinPostAssetBuilder $linkedinBuilder
    ): JsonResponse {
        return $this->generateImageTest(
            $linkedinBuilder,
            $style,
            'LinkedIn Post',
            '1.91:1 (1200x627)'
        );
    }

    /**
     * Test génération Article.
     */
    #[Route('/article/{style}', name: 'article', defaults: ['style' => 'flat_illustration'])]
    public function testArticle(
        string $style,
        ArticleAssetBuilder $articleBuilder
    ): JsonResponse {
        return $this->generateImageTest(
            $articleBuilder,
            $style,
            'Article',
            '16:9 (1200x675)'
        );
    }

    /**
     * Test génération IAB.
     */
    #[Route('/iab/{style}', name: 'iab', defaults: ['style' => 'flat_illustration'])]
    public function testIab(
        string $style,
        IabAssetBuilder $iabBuilder
    ): JsonResponse {
        return $this->generateImageTest(
            $iabBuilder,
            $style,
            'IAB Banner',
            'Variable selon format IAB'
        );
    }

    /**
     * Test tous les styles pour un builder donné.
     */
    #[Route('/all-styles/{builder}', name: 'all_styles')]
    public function testAllStyles(
        string $builder,
        InstagramPostAssetBuilder $instagramBuilder,
        FacebookPostAssetBuilder $facebookBuilder,
        LinkedinPostAssetBuilder $linkedinBuilder,
        ArticleAssetBuilder $articleBuilder,
        IabAssetBuilder $iabBuilder
    ): JsonResponse {
        $builderMap = [
            'instagram' => ['builder' => $instagramBuilder, 'name' => 'Instagram Post', 'format' => '1:1 (1080x1080)'],
            'facebook' => ['builder' => $facebookBuilder, 'name' => 'Facebook Post', 'format' => '1.91:1 (1200x628)'],
            'linkedin' => ['builder' => $linkedinBuilder, 'name' => 'LinkedIn Post', 'format' => '1.91:1 (1200x627)'],
            'article' => ['builder' => $articleBuilder, 'name' => 'Article', 'format' => '16:9 (1200x675)'],
            'iab' => ['builder' => $iabBuilder, 'name' => 'IAB Banner', 'format' => 'Variable'],
        ];

        if (! isset($builderMap[$builder])) {
            return $this->json([
                'success' => false,
                'error' => sprintf('Builder "%s" inconnu. Builders disponibles: %s', $builder, implode(', ', array_keys($builderMap))),
            ], Response::HTTP_BAD_REQUEST);
        }

        $config = $builderMap[$builder];
        $results = [];

        foreach (self::STYLES as $style) {
            $startTime = microtime(true);

            try {
                $result = $this->generateImageTest(
                    $config['builder'],
                    $style,
                    $config['name'],
                    $config['format']
                );

                $content = $result->getContent();

                if (false === $content) {
                    throw new \RuntimeException('Failed to get response content');
                }

                $data = json_decode($content, true);

                if (! is_array($data)) {
                    throw new \RuntimeException('Failed to decode JSON response');
                }

                $data['duration_seconds'] = round(microtime(true) - $startTime, 2);
                $results[$style] = $data;
            } catch (\Exception $e) {
                $results[$style] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'duration_seconds' => round(microtime(true) - $startTime, 2),
                ];
            }
        }

        return $this->json([
            'success' => true,
            'builder' => $builder,
            'builder_name' => $config['name'],
            'format' => $config['format'],
            'total_styles' => count(self::STYLES),
            'results' => $results,
        ]);
    }

    /**
     * Méthode privée pour générer et tester une image.
     */
    private function generateImageTest(
        InstagramPostAssetBuilder|FacebookPostAssetBuilder|LinkedinPostAssetBuilder|ArticleAssetBuilder|IabAssetBuilder $builder,
        string $style,
        string $builderName,
        string $format
    ): JsonResponse {
        // Valider le style
        if (! in_array($style, self::STYLES, true)) {
            return $this->json([
                'success' => false,
                'error' => sprintf('Style "%s" invalide. Styles disponibles: %s', $style, implode(', ', self::STYLES)),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Données de test
        $strategy = [
            'name' => sprintf('Test %s - Style %s', $builderName, ucfirst($style)),
            'description' => 'Test automatique de génération d\'images marketing via Mistral Agents API',
        ];

        $project = [
            'company_name' => 'myCfia',
            'sector' => 'Marketing Automation & AI',
            'websiteUrl' => 'https://mycfia.example.com',
        ];

        // Options de génération d'images
        $options = [
            'generate_images' => true,
            'image_style' => $style,
        ];

        $startTime = microtime(true);

        try {
            // Générer l'asset avec image
            $asset = $builder->build($strategy, $project, null, $options);

            $duration = round(microtime(true) - $startTime, 2);

            // Vérifier si l'image a été générée
            if (! isset($asset['image'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Aucune image générée dans l\'asset',
                    'asset' => $asset,
                    'duration_seconds' => $duration,
                ]);
            }

            $imageData = $asset['image']['image_data'];
            $fileId = $asset['image']['file_id'] ?? 'unknown';

            // Sauvegarder l'image pour vérification visuelle
            $imageBytes = base64_decode($imageData);
            $filename = sprintf(
                '/tmp/test_%s_%s_%s.png',
                strtolower(str_replace(' ', '_', $builderName)),
                $style,
                time()
            );
            file_put_contents($filename, $imageBytes);

            return $this->json([
                'success' => true,
                'builder' => $builderName,
                'format' => $format,
                'style' => $style,
                'file_id' => $fileId,
                'size_kb' => round(strlen($imageBytes) / 1024, 2),
                'duration_seconds' => $duration,
                'saved_to' => $filename,
                'image_base64' => $imageData,
            ]);
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'builder' => $builderName,
                'format' => $format,
                'style' => $style,
                'duration_seconds' => $duration,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
