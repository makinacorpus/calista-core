<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Calista configuration structure
 *
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

                // Global configuration
                ->arrayNode('config')
                    ->children()
                        ->scalarNode('theme')->defaultValue(CalistaExtension::DEFAULT_THEME_TEMPLATE)->end()
                    ->end()
                ->end()

                // Definition of pages
                ->arrayNode('pages')
                    ->normalizeKeys(true)
                    ->prototype('array')
                        ->children()
                            ->variableNode('extra')->end()
                            ->variableNode('input')->end()
                            ->variableNode('view')->isRequired()->end()
                            ->scalarNode('datasource')->isRequired()->end()
                            ->scalarNode('id')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
