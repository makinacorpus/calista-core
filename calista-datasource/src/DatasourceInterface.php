<?php

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Query\Query;

/**
 * Datasource: fetches objects.
 */
interface DatasourceInterface
{
    /**
     * Get item class
     *
     * Item class will enable the ProperyInfo component usage over your objects.
     * Whenever you have very specific classes you also should write your own
     * property extractors.
     *
     * @return null|string
     *   Empty string means we don't know the data type
     */
    public function getItemClass(): string;

    /**
     * Get ready to display filters
     *
     * @return \MakinaCorpus\Calista\Query\Filter[]
     *   Keys do not matter
     */
    public function getFilters(): array;

    /**
     * Get sort fields
     *
     * @return string
     *   Keys are fields, values are human readable labels
     */
    public function getSorts(): array;

    /**
     * Does this datasource streams data
     */
    public function supportsStreaming(): bool;

    /**
     * Does this datasource supports pagination
     */
    public function supportsPagination(): bool;

    /**
     * Does this datasource supports full text search
     */
    public function supportsFulltextSearch(): bool;

    /**
     * Get items to display
     *
     * This should NOT return rendered items but loaded items or item
     * identifiers depending upon your implementation: only the Display
     * instance will really display items, since it may change the display
     * depending upon current context
     */
    public function getItems(Query $query): DatasourceResultInterface;

    /**
     * Given an arbitrary list of identifiers that this datasource should
     * understand, return false if any of the given item identifiers are part
     * of this datasource data set.
     *
     * Item identifiers are given in an arbitrary fashion, the datasource might
     * not even understand the concept of identifiers.
     *
     * This can be used by external code to implement complex form widget using
     * administration screens as item selectors, for example, but this module
     * does not care about it.
     */
    public function validateItems(Query $query, array $idList): bool;
}
