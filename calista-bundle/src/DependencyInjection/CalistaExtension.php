<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Calista\Bridge\Symfony\Controller\PageRenderer;
use MakinaCorpus\Calista\Twig\View\TwigViewRenderer;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\ViewManager;
use MakinaCorpus\Calista\View\ViewRendererRegistry;
use MakinaCorpus\Calista\View\ViewRendererRegistry\ContainerViewRendererRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Twig\Environment;

/**
 * @codeCoverageIgnore
 */
final class CalistaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        // From the configured pages, build services.
        $pageDefinitions = [];
        foreach ($configs as $config) {
            // This was a side effect due to Drupal 7 with sf_dic module was
            // loading multiple configuration files without proper merging,
            // this should not exist. But it works, so let's keep it.
            $config = $this->processConfiguration($configuration, [$config]);
            if (isset($config['pages'])) {
                foreach ($config['pages'] as $id => $array) {
                    $pageDefinitions[$array['id'] ?? $id] = $array;
                }
            }
        }

        $container->setParameter('calista_theme', $config['config']['theme'] ?? TwigViewRenderer::DEFAULT_THEME_TEMPLATE);

        $this->registerPropertyRenderer($container);
        $this->registerViewRendererRegistry($container);
        $this->registerViewManager($container);
        $this->registerViewFactory($container, $pageDefinitions);
        $this->registerPageRenderer($container);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('view.yml');

        if (\class_exists(Environment::class)) {
            $loader->load('twig.yml');
        }
        if (\class_exists('Box\\Spout\\Writer\\WriterFactory')) {
            $loader->load('spout.yml');
        }
    }

    private function registerViewRendererRegistry(ContainerBuilder $container): void
    {
        $definition = new Definition();
        $definition->setClass(ContainerViewRendererRegistry::class);
        // Will be populated using a compiler pass.
        $definition->setArguments([[]]);
        $definition->addMethodCall('setContainer', [new Reference('service_container')]);
        $definition->setPrivate(true);

        $container->setDefinition('calista.view.renderer_registry.container', $definition);
        $container->setAlias('calista.view.renderer_registry', 'calista.view.renderer_registry.container');
        $container->setAlias(ViewRendererRegistry::class, 'calista.view.renderer_registry');
    }

    private function registerViewManager(ContainerBuilder $container): void
    {
        $definition = new Definition();
        $definition->setClass(ViewManager::class);
        $definition->setArguments([new Reference('calista.view.renderer_registry'), new Reference('event_dispatcher')]);
        $definition->setPrivate(true);

        $container->setDefinition('calista.view.manager', $definition);
        $container->setAlias(ViewManager::class, 'calista.view.manager');
    }

    private function registerPropertyRenderer(ContainerBuilder $container): void
    {
        $definition = new Definition();
        $definition->setClass(PropertyRenderer::class);
        $definition->setArguments([new Reference('property_accessor')]);
        $definition->setPrivate(true);

        $container->setDefinition('calista.property_renderer', $definition);
        $container->setAlias(PropertyRenderer::class, 'calista.property_renderer');
    }

    /**
     * @deprecated
     */
    private function registerViewFactory(ContainerBuilder $container, array $pageDefinitions): void
    {
        $definition = new Definition();
        $definition->setClass(ViewFactory::class);
        $definition->setArguments([
            new Reference('calista.view.renderer_registry'),
            $pageDefinitions,
        ]);
        $definition->addMethodCall('setContainer', [new Reference('service_container')]);
        $definition->setPrivate(true);

        $container->setDefinition('calista.view.factory', $definition);
        $container->setAlias(ViewFactory::class, 'calista.view.factory');

        // Kept for backward compatibility, but you should stop using this.
        $container->setAlias('calista.view_factory', 'calista.view.factory');
    }

    private function registerPageRenderer(ContainerBuilder $container): void
    {
        $definition = new Definition();
        $definition->setClass(PageRenderer::class);
        $definition->setArguments([new Reference('calista.view.factory')]);
        $definition->setPrivate(true);

        $container->setDefinition('calista.page_renderer', $definition);
        $container->setAlias(PageRenderer::class, 'calista.page_renderer');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new CalistaConfiguration();
    }
}
