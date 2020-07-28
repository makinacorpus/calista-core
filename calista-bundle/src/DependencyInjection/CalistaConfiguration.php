<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Calista\Twig\View\TwigViewRenderer;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * @codeCoverageIgnore
 */
final class CalistaConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('calista');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('config')
                    ->children()
                        ->scalarNode('theme')->defaultValue(TwigViewRenderer::DEFAULT_THEME_TEMPLATE)->end()
                    ->end()
                ->end()
                ->variableNode('pages')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
