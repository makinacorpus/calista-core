<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @codeCoverageIgnore
 */
class RegisterNamespaceCompilerPass implements CompilerPassInterface
{
    private string $twigLoaderId;

    /**
     * Default constructor
     */
    public function __construct($twigLoaderId = 'twig.loader')
    {
        $this->twigLoaderId = $twigLoaderId;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $id = $this->twigLoaderId;

        if ($container->hasAlias($id)) {
            $id = (string)$container->getAlias($id);
        }

        if ($container->hasDefinition($id)) {
            $definition = $container->getDefinition($id);
            $definition->addMethodCall('addPath', [dirname(dirname(__DIR__)) . '/templates', 'calista']);
            $definition->addMethodCall('addPath', [dirname(dirname(__DIR__)) . '/templates', 'Calista']);
        }
    }
}
