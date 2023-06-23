<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\Calista\View\ViewRenderer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;

final class ViewRendererRegisterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // @codeCoverageIgnoreStart
        if (!$container->hasDefinition('calista.view.renderer_registry.container')) {
            return;
        }
        // @codeCoverageIgnoreEnd
        $containerRegistryDefinition = $container->getDefinition('calista.view.renderer_registry.container');

        $serviceMap = [];
        $locatorMap = [];
        foreach ($container->findTaggedServiceIds('calista.view') as $id => $attributes) {
            $viewRendererDefinition = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($viewRendererDefinition->getClass());
            $refClass = new \ReflectionClass($class);

            // @codeCoverageIgnoreStart
            if (!$refClass->implementsInterface(ViewRenderer::class)) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" must implement interface "%s".', $id, ViewRenderer::class));
            }
            // @codeCoverageIgnoreEnd

            if (empty($attributes[0]['id'])) {
                $typeId = $viewRendererDefinition->getClass();
            } else {
                $typeId = $attributes[0]['id'];
            }

            $serviceMap[$typeId] = $serviceMap[$id] = $id;
            $locatorMap[$typeId] = $locatorMap[$id] = new Reference($id);
        }

        $containerRegistryDefinition->setArgument(0, $serviceMap);
        $containerRegistryDefinition->setArgument(1, ServiceLocatorTagPass::register($container, $locatorMap));
    }
}
