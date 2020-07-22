<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Query\Query;

/**
 * Datasource: fetches objects.
 */
interface DatasourceInterface
{
    /**
     * Get ready to display filters.
     *
     * @return \MakinaCorpus\Calista\Query\Filter[]
     *   Keys do not matter.
     */
    public function getFilters(): array;

    /**
     * Get sort fields.
     *
     * @return string
     *   Keys are fields, values are human readable labels.
     */
    public function getSorts(): array;

    /**
     * Get items to display.
     *
     * This should NOT return rendered items but loaded items or item
     * identifiers depending upon your implementation: only the Display
     * instance will really display items, since it may change the display
     * depending upon current context
     */
    public function getItems(Query $query): DatasourceResultInterface;
}
