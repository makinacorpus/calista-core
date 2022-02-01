<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Calista\Bridge\Symfony\Controller\RestController;
use MakinaCorpus\Calista\Twig\Extension\BlockExtension;
use MakinaCorpus\Calista\Twig\View\DefaultTwigBlockRenderer;
use MakinaCorpus\Calista\View\CustomViewBuilder;
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

final class CalistaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerThemeAndTemplates($container, $config['config']);
        $this->registerPropertyRenderer($container);
        $this->registerViewRendererRegistry($container);
        $this->registerViewManager($container);
        $this->registerCustomViewBuilders($container);
        $this->registerRestRenderer($container);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('view.yml');

        if (\class_exists(Environment::class)) {
            $loader->load('twig.yml');
        }
        if (\class_exists('Box\\Spout\\Writer\\Common\\Creator\\WriterEntityFactory')) {
            $loader->load('spout.yml');
        }
    }

    private function registerCustomViewBuilders(ContainerBuilder $container): void
    {
        $serviceId = 'calista.bundle.custom_view_renderer_registry';
        $definition = new Definition();
        $definition->setClass(ContainerCustomViewBuilderRegistry::class);
        $definition->addMethodCall('setContainer', [new Reference('service_container')]);
        $definition->setArguments([[]]);
        $definition->setPublic(false);
        $container->setDefinition($serviceId, $definition);
        $container->setAlias(CustomViewBuilder::class, $serviceId);
    }

    private function registerRestRenderer(ContainerBuilder $container): void
    {
        $serviceId = 'calista.bundle.rest_controller';
        $definition = new Definition();
        $definition->setClass(RestController::class);
        $definition->setArguments([
            new Reference('calista.bundle.custom_view_renderer_registry'),
            new Reference('calista.view.manager'),
            new Reference('calista.property_renderer'),
            new Reference('router'),
        ]);
        $definition->setPublic(true);
        $definition->addTag('controller.service_arguments');
        $container->setDefinition('calista.bundle.rest_controller', $definition);
        $container->setAlias(RestController::class, $serviceId);
    }

    private function registerThemeAndTemplates(ContainerBuilder $container, array $config): void
    {
        $defaultTheme = $config['theme'];
        $defaultTemplates = null;
        $defaultPageTemplate = null;

        // Order matters: first items in array will override those after,
        // if theme is a single string, such as 'default' or 'bootstrap',
        // we initialize templates from this theme.
        // If theme is a custom one we don't recognize, we assume that
        // it's a Twig template identifier user gave us, case in which
        // the user need to manually set its own filter theme in the
        // 'templates' section.
        switch ($defaultTheme) {
            case 'bootstrap':
            case 'bootstrap4':
            case '@calista/page/page-bootstrap4.html.twig':
                $defaultPageTemplate = '@calista/page/page-bootstrap4.html.twig';
                $defaultTemplates = [
                    '@calista/page/filter-bootstrap4.html.twig',
                    '@calista/page/page-bootstrap4.html.twig',
                ];
                break;
            case '@calista/page/page-bootstrap3.html.twig':
            case 'bootstrap3':
                $defaultPageTemplate = '@calista/page/page-bootstrap3.html.twig';
                $defaultTemplates = [
                    '@calista/page/filter-bootstrap3.html.twig',
                    '@calista/page/page-bootstrap3.html.twig',
                ];
                break;
            case 'default':
            case '@calista/page/page.html.twig':
                $defaultPageTemplate = '@calista/page/page.html.twig';
                $defaultTemplates = [
                    '@calista/page/filter.html.twig',
                    '@calista/page/page.html.twig',
                ];
                break;
            default:
                $defaultPageTemplate = $defaultTheme;
                $defaultTemplates = [
                    $defaultTheme,
                    '@calista/page/filter.html.twig',
                    '@calista/page/page.html.twig',
                ];
                break;
        }

        $container->setParameter('calista_theme', $defaultPageTemplate);

        // Now prepend user given default templates, in order. If a template
        // already exist in array, the user wanted to explicitely reorder it
        // so let's do that.
        foreach (($config['templates'] ?? []) as $templateName) {
            if (false !== ($index = \array_search($templateName, $defaultTemplates))) {
                unset($defaultTemplates[$index]);
            }
            \array_unshift($defaultTemplates, $templateName);
        }

        $definition = new Definition();
        $definition->setClass(DefaultTwigBlockRenderer::class);
        $definition->setArguments([new Reference('twig'), $defaultTemplates]);
        $definition->setPublic(false);
        $container->setDefinition('calista.twig.default_block_renderer', $definition);

        $definition = new Definition();
        $definition->setClass(BlockExtension::class);
        $definition->setArguments([new Reference('calista.twig.default_block_renderer')]);
        $definition->setPublic(false);
        $definition->addTag('twig.extension');
        $container->setDefinition('calista.twig.block_extension', $definition);
    }

    private function registerViewRendererRegistry(ContainerBuilder $container): void
    {
        $definition = new Definition();
        $definition->setClass(ContainerViewRendererRegistry::class);
        // Will be populated using a compiler pass.
        $definition->setArguments([[]]);
        $definition->addMethodCall('setContainer', [new Reference('service_container')]);
        $definition->setPublic(false);

        $container->setDefinition('calista.view.renderer_registry.container', $definition);
        $container->setAlias('calista.view.renderer_registry', 'calista.view.renderer_registry.container');
        $container->setAlias(ViewRendererRegistry::class, 'calista.view.renderer_registry');
    }

    private function registerViewManager(ContainerBuilder $container): void
    {
        $definition = new Definition();
        $definition->setClass(ViewManager::class);
        $definition->setArguments([
            new Reference('calista.view.renderer_registry'),
            new Reference('event_dispatcher'),
            new Reference('calista.bundle.custom_view_renderer_registry'),
        ]);
        $definition->setPublic(false);

        $container->setDefinition('calista.view.manager', $definition);
        $container->setAlias(ViewManager::class, 'calista.view.manager');
    }

    private function registerPropertyRenderer(ContainerBuilder $container): void
    {
        $definition = new Definition();
        $definition->setClass(PropertyRenderer::class);
        $definition->setArguments([new Reference('property_accessor')]);
        $definition->setPublic(false);

        $container->setDefinition('calista.property_renderer', $definition);
        $container->setAlias(PropertyRenderer::class, 'calista.property_renderer');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new CalistaConfiguration();
    }
}
