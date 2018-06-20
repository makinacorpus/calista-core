<?php

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Query\Query;

/**
 * Result iterator interface
 *
 * count() method will return the current item batch items, depending upon the
 * query range.
 */
interface DatasourceResultInterface extends \Traversable, \Countable
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
     * Datasource can provide its own set of known properties, useful for view
     * introspection if you don't want to or can't rely upon the property info
     * component introspection
     *
     * @return PropertyDescription[]
     */
    public function getProperties(): array;

    /**
     * Can this datasource stream large datasets
     *
     * Most result iterators should never preload items, and should allow items
     * to be iterated with large datasets without compromising the PHP memory
     * consumption, nevertheless, some might not be able to do this, case in
     * which this method should return false to indicate other developers this
     * must not be used for things like data to file export/streaming.
     */
    public function canStream(): bool;

    /**
     * Set total item count
     */
    public function setTotalItemCount(int $count);

    /**
     * Did the datasource provided an item count
     */
    public function hasTotalItemCount(): bool;

    /**
     * Get total item count
     */
    public function getTotalCount(): int;

    /**
     * Get page count for the given limit
     */
    public function getPageCount(int $limit = Query::LIMIT_DEFAULT): int;

    /**
     * Compute the current page range
     *
     * @param int $page
     *   Relative int to compute pages from
     * @param int $limit
     *   Current query limit
     *
     * @return int[]
     */
    public function getPageRange(int $page = 1, int $limit = Query::LIMIT_DEFAULT): array;
}
