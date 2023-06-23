<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;

/**
 * Registers custom view builders.
 */
final class CustomViewBuilderRegisterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // @codeCoverageIgnoreStart
        if (!$container->hasDefinition('calista.bundle.custom_view_renderer_registry')) {
            return;
        }
        // @codeCoverageIgnoreEnd
        $containerRegistryDefinition = $container->getDefinition('calista.bundle.custom_view_renderer_registry');

        $serviceMap = [];
        $locatorMap = [];
        foreach ($container->findTaggedServiceIds('calista.view_builder') as $id => $attributes) {
            $viewRendererDefinition = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($viewRendererDefinition->getClass());
            $refClass = new \ReflectionClass($class);

            // @codeCoverageIgnoreStart
            if (!$refClass->implementsInterface(CustomViewBuilder::class)) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" must implement interface "%s".', $id, CustomViewBuilder::class));
            }
            // @codeCoverageIgnoreEnd

            if (isset($attributes[0]['name'])) {
                $typeId = $attributes[0]['name'];
                $serviceMap[$typeId] = $id;
            }
            if ($typeId = $this->findNameFromClassStaticProperties($class)) {
                $serviceMap[$typeId] = $id;
            }
            // Allow services to be found by their names.
            $serviceMap[$refClass->getName()] = $serviceMap[$typeId] = $id;
            $locatorMap[$refClass->getName()] = $locatorMap[$typeId] = new Reference($id);
        }

        $containerRegistryDefinition->setArgument(0, $serviceMap);
        $containerRegistryDefinition->setArgument(1, ServiceLocatorTagPass::register($container, $locatorMap));
    }

    private function findNameFromClassStaticProperties(string $className): ?string
    {
        $refClass = new \ReflectionClass($className);

        if (!$refClass->hasProperty('name') || !$refClass->getProperty('name')->isStatic()) {
            return null;
        }

        return $refClass->getStaticPropertyValue('name');
    }
}
