<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\Query;

/**
 * Empty datasource
 */
final class EmptyDatasource extends AbstractDatasource
{
    private array $allowedFilters = [];
    private array $allowedSorts = [];

    public function __construct(array $allowedFilters = [], array $allowedSorts = [])
    {
        $this->allowedFilters = $allowedFilters;
        $this->allowedSorts = $allowedSorts;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return \array_map(
            function ($name) {
                return new Filter($name);
            },
            $this->allowedFilters
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts(): array
    {
        return \array_combine($this->allowedSorts, $this->allowedSorts);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query): DatasourceResultInterface
    {
        return new DefaultDatasourceResult();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch(): bool
    {
        return true;
    }
}
