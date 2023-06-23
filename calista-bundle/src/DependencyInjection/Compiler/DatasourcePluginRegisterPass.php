<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\Calista\Datasource\Plugin\DatasourceResultConverter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;

final class DatasourcePluginRegisterPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // @codeCoverageIgnoreStart
        if (!$container->hasDefinition('calista.bundle.datasource_plugin_registry')) {
            return;
        }
        // @codeCoverageIgnoreEnd
        $containerRegistryDefinition = $container->getDefinition('calista.bundle.datasource_plugin_registry');

        $serviceMap = [];
        $converterMap = [];

        foreach ($this->findAndSortTaggedServices('calista.datasource_plugin', $container) as $reference) {
            \assert($reference instanceof Reference);
            $id = (string) $reference;

            $datasourcePluginDefinition = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($datasourcePluginDefinition->getClass());
            $refClass = new \ReflectionClass($class);

            // @codeCoverageIgnoreStart
            if ($refClass->implementsInterface(DatasourceResultConverter::class)) {
                $converterMap[] = $id;
                $serviceMap[$id] = new Reference($id);
            } else {
                throw new \InvalidArgumentException(\sprintf(
                    'Service "%s" must implement on the following interfaces: "%s".',
                    $id,
                    \implode('", "', [
                        DatasourceResultConverter::class,
                    ])
                ));
            }
            // @codeCoverageIgnoreEnd
        }

        if ($converterMap) {
            $containerRegistryDefinition->setArgument(0, ServiceLocatorTagPass::register($container, $serviceMap));
            $containerRegistryDefinition->setArgument(1, $converterMap);
        }
    }
}
