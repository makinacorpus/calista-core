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
     * {@inheritdoc}
     */
    public function canStream(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalItemCount(int $count): void
    {
        $this->totalCount = $count;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTotalItemCount(): bool
    {
        return null !== $this->totalCount;
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
    public function getTotalCount(): int
    {
        return $this->totalCount ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageRange(int $page = 1, int $limit = Query::LIMIT_DEFAULT): array
    {
        $num = \ceil($this->getTotalCount() / $limit);
        $min = \max([$page - 2, 1]);
        $max = \min([$page + 2, $num]);

        if ($max - $min < 4) {
            if (1 == $min) {
                return \range(1, \min([5, $num]));
            } else {
                return \range(\max([$num - 4, 1]), $num);
            }
        } else {
            return \range($min, $max);
        }
    }
}
