<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @codeCoverageIgnore
 */
final class CalistaExtension extends Extension
{
    const DEFAULT_THEME_TEMPLATE = '@calista/page/page.html.twig';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        // From the configured pages, build services
        foreach ($configs as $config) {
            // Do not process everything at once, it will erase array keys
            // of all pages definitions except those from the very first config
            // file, and break all our services identifiers
            $config = $this->processConfiguration($configuration, [$config]);

            if (isset($config['pages'])) {
                foreach ($config['pages'] as $id => $array) {

                    // Determine both service and page identifier
                    $serviceId = 'calista.config_page.' . $id;
                    $pageId = empty($array['id']) ? $id : $array['id'];

                    $definition = new Definition();
                    $definition->setClass(ConfigPageDefinition::class);
                    $definition->setArguments([$array, new Reference('calista.view_factory')]);
                    $definition->setPublic(true); // Lazy loading...
                    $definition->addTag('calista.page_definition', ['id' => $pageId]);

                    $container->addDefinitions([$serviceId => $definition]);
                }
            }
        }

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yml');
        $loader->load('view.yml');

        if (\class_exists(\Twig_Environment::class)) {
            $loader->load('twig.yml');
        }
        if (\class_exists(ContainerAwareCommand::class)) {
            $loader->load('commands.yml');
        }
        if (\class_exists('Box\\Spout\\Writer\\WriterFactory')) {
            $loader->load('spout.yml');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new CalistaConfiguration();
    }
}
