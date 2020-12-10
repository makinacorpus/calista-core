<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bundle\Tests;

use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\CalistaExtension;
use MakinaCorpus\Calista\Twig\Tests\TestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class KernelConfigurationTest extends TestCase
{
    private function getContainer()
    {
        // Code inspired by the SncRedisBundle, all credits to its authors.
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug'=> false,
            'kernel.bundles' => [
                CalistaExtension::class => ['all' => true],
            ],
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => \dirname(__DIR__),
        ]));

        $twigLoaderDefinition = new Definition();
        $twigLoaderDefinition->setClass(ArrayLoader::class);
        $twigLoaderDefinition->setArguments([TestFactory::createTestTemplatesLoaderDefinition()]);
        $container->setDefinition('twig_loader', $twigLoaderDefinition);

        // Define a minimal custom Twig service.
        $twigEnvDefinition = new Definition();
        $twigEnvDefinition->setClass(Environment::class);
        $twigEnvDefinition->setArguments([
            new Reference('twig_loader'),
            [
                'debug' => true,
                'strict_variables' => true,
                'autoescape' => 'html',
                'cache' => false,
                'auto_reload' => null,
                'optimizations' => -1,
            ]
        ]);
        $container->setDefinition('twig', $twigEnvDefinition);

        return $container;
    }

    private function getMinimalConfig(): array
    {
        return [
            'config' => [
                'theme' => 'bootstrap4',
                'templates' => [
                    '@calista/test/unit/first-extend.html.twig',
                    '@calista/test/unit/first.html.twig',
                ]
            ],
        ];
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testTaggedServicesConfigLoad()
    {
        $extension = new CalistaExtension();
        $config = $this->getMinimalConfig();
        $extension->load([$config], $container = $this->getContainer());

        $container->compile();
    }
}
