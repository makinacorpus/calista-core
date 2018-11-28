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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('calista');

        // This is a very concise representation of pages, because it will
        // be validated at runtime using the OptionResolver component; we
        // only describe the required/possible keys and that's pretty much
        // it.
        $rootNode
            ->children()
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
