<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource\Plugin;

interface DatasourcePluginRegistry
{
    /**
     * @return DatasourceResultConverter[]
     */
    public function getResultConverters(): iterable;
}
