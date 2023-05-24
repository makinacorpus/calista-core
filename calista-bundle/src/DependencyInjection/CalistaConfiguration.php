<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class CalistaConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('calista');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode /* @phpstan-ignore-line */
            ->children()
                ->arrayNode('config')
                    ->children()
                        ->scalarNode('theme')->defaultValue('default')->end()
                        ->arrayNode('templates')
                            ->prototype('scalar')->end()
                            ->example(['@My/calista/filter-custom.html.twig'])
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
