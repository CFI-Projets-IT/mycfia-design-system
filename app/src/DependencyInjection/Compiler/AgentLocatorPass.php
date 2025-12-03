<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * CompilerPass pour forcer l'inclusion des Tools dans le service locator agent.locator.
 *
 * Fix pour le problème où Symfony optimise et retire les Tools non directement référencés.
 */
class AgentLocatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('agent.locator')) {
            return;
        }

        $locator = $container->getDefinition('agent.locator');
        $services = $locator->getArgument(0);

        // Forcer l'ajout des Tools dans le service locator
        $tools = [
            'Gorillias\MarketingBundle\Tool\CompetitorIntelligenceTool',
            'Gorillias\MarketingBundle\Tool\BrandStyleAnalyzerTool',
            'Gorillias\MarketingBundle\Tool\BudgetOptimizerTool',
            'Gorillias\MarketingBundle\Tool\ProjectContextAnalyzerTool',
        ];

        foreach ($tools as $toolId) {
            if ($container->hasDefinition($toolId)) {
                $services[$toolId] = new Reference($toolId);
            }
        }

        $locator->setArgument(0, $services);
    }
}
