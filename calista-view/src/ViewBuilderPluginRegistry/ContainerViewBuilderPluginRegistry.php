<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewBuilderPluginRegistry;

use MakinaCorpus\Calista\View\ViewBuilderPluginRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class ContainerViewBuilderPluginRegistry implements ViewBuilderPluginRegistry, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @param array<string,string> */
    private array $serviceMap = [];

    /**
     * @param array<string,string> $serviceMap
     */
    public function __construct(array $serviceMap)
    {
        $this->serviceMap = $serviceMap;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        return (
            function () {
                foreach ($this->serviceMap as $serviceName) {
                    yield $this->container->get($serviceName);
                }
            }
        )();
    }
}
