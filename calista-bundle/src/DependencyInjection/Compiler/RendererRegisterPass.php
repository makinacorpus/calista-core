<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;

/**
 * Registers page definitions
 */
final class RendererRegisterPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // @codeCoverageIgnoreStart
        if (!$container->hasDefinition('calista.property_renderer')) {
            return;
        }
        // @codeCoverageIgnoreEnd
        $definition = $container->getDefinition('calista.property_renderer');

        // Register custom action providers
        foreach ($this->findAndSortTaggedServices('calista.property_renderer', $container) as $id => $attributes) {
            $definition->addMethodCall(['addRenderer', [new Reference($id)]]);
        }
    }
}
