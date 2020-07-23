<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

/**
 * Result iterator interface
 *
 * count() method will return the current item batch items, depending upon the
 * query range.
 */
interface DatasourceResultInterface extends \Traversable, \Countable
{
    /**
     * Datasource can provide its own set of known properties, useful for view
     * introspection if you don't want to or can't rely upon the property info
     * component introspection.
     *
     * @return PropertyDescription[]
     */
    public function getProperties(): array;

    /**
     * Get limit if known, otherwise 0 (which means no limit as well).
     */
    public function getLimit(): int;

    /**
     * Get current page if known, otherwise 1.
     */
    public function getCurrentPage(): int;

    /**
     * Get total item count if known, other wise 0 (which can be a valid value as well).
     */
    public function getTotalCount(): ?int;

    /**
     * Get page count for the given limit.
     */
    public function getPageCount(): int;
}
