<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bundle\Tests;

use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\CalistaExtension;
use MakinaCorpus\Calista\Twig\Tests\TestFactory;
use MakinaCorpus\Calista\Twig\Tests\TestTwigLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

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

        $propertyAccessorDefinition = new Definition();
        $propertyAccessorDefinition->setClass(PropertyAccessor::class);
        $propertyAccessorDefinition->setFactory(PropertyAccess::class . '::createPropertyAccessor');
        $propertyAccessorDefinition->setPublic(false);
        $container->setDefinition('property_accessor', $propertyAccessorDefinition);

        $eventDispatcherDefinition = new Definition();
        $eventDispatcherDefinition->setClass(EventDispatcher::class);
        $eventDispatcherDefinition->setPublic(false);
        $container->setDefinition('event_dispatcher', $eventDispatcherDefinition);

        $twigLoaderDefinition = new Definition();
        $twigLoaderDefinition->setClass(TestTwigLoader::class);
        $twigLoaderDefinition->setArguments([TestFactory::createTestTemplatesLoaderDefinition()]);
        $container->setDefinition('twig_loader', $twigLoaderDefinition);

        $routerDefinition = new Definition();
        $routerDefinition->setClass(UrlGeneratorInterface::class);
        $routerDefinition->setPublic(false);
        $container->setDefinition('router', $routerDefinition);

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
        self::expectNotToPerformAssertions();

        $extension = new CalistaExtension();
        $config = $this->getMinimalConfig();

        $container = $this->getContainer();

        $container->registerExtension($extension);
        $extension->load([$config], $container);

        $container->compile();
    }
}
