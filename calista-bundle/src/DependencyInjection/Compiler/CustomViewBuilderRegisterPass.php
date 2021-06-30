<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

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
            } else {
                $typeId = $this->findNameFromClassStaticProperties($class) ?? $class;
            }

            $viewRendererDefinition->setPublic(true);

            $serviceMap[$typeId] = $id;
        }

        $containerRegistryDefinition->setArgument(0, $serviceMap);
    }

    private function findNameFromClassStaticProperties(string $className): ?string
    {
        $refClass = new \ReflectionClass($className);

        return $refClass->getStaticPropertyValue('name');
    }
}
