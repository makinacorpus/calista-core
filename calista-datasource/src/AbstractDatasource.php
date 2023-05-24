<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

/**
 * Base implementation which leaves null a few mathods
 */
abstract class AbstractDatasource implements DatasourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts(): array
    {
        return [];
    }

    /**
     * Create an empty result
     */
    protected function createEmptyResult(): DatasourceResultInterface
    {
        return new DefaultDatasourceResult();
    }

    /**
     * Create default result iterator with the provided information
     *
     * @param array|\Traversable $items
     *
     * @return DefaultDatasourceResult
     */
    protected function createResult($items, int $limit = 0, int $total = 0, int $page = 1): DatasourceResultInterface
    {
        if (!\is_array($items) && !$items instanceof \Traversable && !\is_callable($items)) {
            throw new \LogicException("given items are nor an array nor a \Traversable instance nor a callable");
        }

        $result = new DefaultDatasourceResult($items);
        $result->setPagerInformation($limit, $total, $page);

        return $result;
    }
}
