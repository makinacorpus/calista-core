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
     * @param null|int $totalCount
     *
     * @return DefaultDatasourceResult
     */
    protected function createResult($items, $totalCount = null): DatasourceResultInterface
    {
        if (!\is_array($items) && !$items instanceof \Traversable && !\is_callable($items)) {
            throw new \LogicException("given items are nor an array nor a \Traversable instance nor a callable");
        }

        $result = new DefaultDatasourceResult($items);

        if (null !== $totalCount) {
            $result->setTotalItemCount($totalCount);
        }

        return $result;
    }
}
