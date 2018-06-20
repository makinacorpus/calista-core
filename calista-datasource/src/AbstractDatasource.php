<?php

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Query\Query;

/**
 * Base implementation which leaves null a few mathods
 */
abstract class AbstractDatasource implements DatasourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getItemClass(): string
    {
        return '';
    }

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
     * {@inheritdoc}
     */
    public function supportsStreaming(): bool
    {
        return false; // Sensible default
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPagination(): bool
    {
        return true; // Sensible default
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch(): bool
    {
        return false; // Sensible default
    }

    /**
     * {@inheritdoc}
     */
    public function validateItems(Query $query, array $idList): bool
    {
        return false;
    }

    /**
     * Create an empty result
     */
    protected function createEmptyResult(): DatasourceResultInterface
    {
        return new DefaultDatasourceResult($this->getItemClass(), []);
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
        if (!is_array($items) && !$items instanceof \Traversable && is_callable($items)) {
            throw new \LogicException("given items are nor an array nor a \Traversable instance nor a callable");
        }

        $result = new DefaultDatasourceResult($this->getItemClass(), $items);

        if (null !== $totalCount) {
            $result->setTotalItemCount($totalCount);
        }

        return $result;
    }
}
