<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Query\Query;

/**
 * Basics for result iterators
 */
trait DatasourceResultTrait /* implements DatasourceResultInterface */
{
    /** @var PropertyDescription[] */
    private array $properties = [];
    private ?int $totalCount = null;

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return $this->properties ?? [];
    }

    /**
     * Set total item count, for pager.
     */
    public function setTotalItemCount(int $count): void
    {
        $this->totalCount = $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageCount(int $limit = Query::LIMIT_DEFAULT): int
    {
        return (int)(null !== $this->totalCount ? \ceil($this->totalCount / $limit) : 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }
}
