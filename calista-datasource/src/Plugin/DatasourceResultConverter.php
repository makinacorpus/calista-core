<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource\Plugin;

use MakinaCorpus\Calista\Datasource\DatasourceResult;

/**
 * Datasource result converter allow third party API to be plugged over calista
 * without creating an hard dependency.
 *
 * Internally, calista can always work with any iterable as result set, but it
 * then cannot guess page, limit and total values, which means it will loose
 * pagination capabilities.
 *
 * This is useful for example to plug Doctrine result sets over calista, or any
 * other database connector.
 */
interface DatasourceResultConverter
{
    /**
     * Convert items to datasource result.
     */
    public function convert(mixed $items): ?DatasourceResult;
}
