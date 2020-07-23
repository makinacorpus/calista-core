<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

/**
 * Basics for result iterators
 */
trait DatasourceResultTrait /* implements DatasourceResultInterface */
{
    /** @var PropertyDescription[] */
    private array $properties = [];
    private int $limit = 0;
    private int $total = 0;
    private int $page = 1;

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return $this->properties ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): int
    {
        return $this->page;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageCount(): int
    {
        if (!$this->total || !$this->limit) {
            return 1;
        }
        return (int) \ceil($this->total / $this->limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount(): ?int
    {
        return $this->total;
    }

    /**
     * Set pager information.
     */
    public function setPagerInformation(int $limit, int $total, int $page = 1): void
    {
        $this->limit = $limit;
        $this->total = $total;
    }
}
