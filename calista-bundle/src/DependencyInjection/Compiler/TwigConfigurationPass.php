<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Registers page definitions
 */
final class TwigConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $options = [];
        $configs = $container->getExtensionConfig('calista');

        // Aggregate real options
        foreach ($configs as $config) {
            if (isset($config['config'])) {
                $options += $config['config'];
            }
        }

        if ($container->has('twig')) {
            $container->getDefinition('twig')->addMethodCall(
                'addGlobal',
                [
                    'calista_theme',
                    $options['theme'] ?? '@calista/page/page-bootstrap4.html.twig',
                ]
            );
        }
    }
}
