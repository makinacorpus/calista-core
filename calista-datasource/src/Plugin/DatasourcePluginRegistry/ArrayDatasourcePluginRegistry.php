<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource\Plugin\DatasourcePluginRegistry;

use MakinaCorpus\Calista\Datasource\Plugin\DatasourcePluginRegistry;
use MakinaCorpus\Calista\Datasource\Plugin\DatasourceResultConverter;

final class ArrayDatasourcePluginRegistry implements DatasourcePluginRegistry
{
    /**
     * @param DatasourceResultConverter $resultConverters
     */
    public function __construct(
        private array $resultConverters,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getResultConverters(): iterable
    {
        return $this->resultConverters;
    }
}
