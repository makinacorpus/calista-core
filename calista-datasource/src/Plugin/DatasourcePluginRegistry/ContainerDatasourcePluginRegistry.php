<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource\Plugin\DatasourcePluginRegistry;

use MakinaCorpus\Calista\Datasource\Plugin\DatasourcePluginRegistry;
use Psr\Container\ContainerInterface;

final class ContainerDatasourcePluginRegistry implements DatasourcePluginRegistry
{
    /**
     * @param array<string> $converterMap
     */
    public function __construct(
        private ?ContainerInterface $container = null,
        private array $converterMap = [],
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getResultConverters(): iterable
    {
        return (
            function () {
                foreach ($this->converterMap as $serviceName) {
                    yield $this->container->get($serviceName);
                }
            }
        )();
    }
}
