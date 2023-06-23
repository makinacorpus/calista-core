<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewBuilderPluginRegistry;

use MakinaCorpus\Calista\View\ViewBuilderPluginRegistry;
use Psr\Container\ContainerInterface;

final class ContainerViewBuilderPluginRegistry implements ViewBuilderPluginRegistry
{
    /**
     * @param array<string,string> $serviceMap
     */
    public function __construct(
        private array $serviceMap,
        private ?ContainerInterface $container = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        if (!$this->container) {
            throw new \LogicException("Uninitialized object, missing container.");
        }

        return (
            function () {
                foreach ($this->serviceMap as $serviceName) {
                    yield $this->container->get($serviceName);
                }
            }
        )();
    }
}
