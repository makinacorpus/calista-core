<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\Calista\View\ViewBuilderPlugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;

/**
 * Registers custom view builders.
 */
final class ViewBuilderPluginRegisterPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // @codeCoverageIgnoreStart
        if (!$container->hasDefinition('calista.bundle.view_builder_plugin_registry.container')) {
            return;
        }
        // @codeCoverageIgnoreEnd
        $containerRegistryDefinition = $container->getDefinition('calista.bundle.view_builder_plugin_registry.container');

        $serviceMap = [];
        $locatorMap = [];
        foreach ($this->findAndSortTaggedServices('calista.view_builder_plugin', $container) as $reference) {
            \assert($reference instanceof Reference);
            $id = (string) $reference;

            $viewRendererDefinition = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($viewRendererDefinition->getClass());
            $refClass = new \ReflectionClass($class);

            // @codeCoverageIgnoreStart
            if (!$refClass->implementsInterface(ViewBuilderPlugin::class)) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" must implement interface "%s".', $id, ViewBuilderPlugin::class));
            }
            // @codeCoverageIgnoreEnd

            // Allow services to be found by their names.
            $serviceMap[] = $id;
            $locatorMap[$id] = $locatorMap[$refClass->getName()] = new Reference($id);
        }

        $containerRegistryDefinition->setArgument(0, $serviceMap);
        $containerRegistryDefinition->setArgument(1, ServiceLocatorTagPass::register($container, $locatorMap));
    }
}
