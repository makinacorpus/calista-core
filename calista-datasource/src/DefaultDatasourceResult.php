<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

/**
 * Basics for the datasource result interface implementation.
 */
class DefaultDatasourceResult implements \IteratorAggregate, DatasourceResult
{
    use DatasourceResultTrait;

    private ?iterable $items = null;
    private ?int $count = null;

    /**
     * Default constructor
     *
     * @param iterable|callable $items
     * @param PropertyDescription[] $properties
     */
    public function __construct($items = [], array $properties = [])
    {
        // Because we're nice guys, we consider null being valid input.
        if (null === $items) {
            $this->items = [];
        } else {
            if (!\is_iterable($items) && \is_callable($items)) {
                // Given argument is callable, but not iterable, hence it is not
                // a generated, give it a call to find what it may return.
                $items = $items();
            }
            if (!\is_iterable($items)) {
                throw new \InvalidArgumentException("Items must be iterable or callable");
            }
            $this->items = $items;
        }

        foreach ($properties as $index => $property) {
            if (!$property instanceof PropertyDescription) {
                throw new \InvalidArgumentException(\sprintf("property at index %s is not a %s instance", $index, PropertyDescription::class));
            }
        }

        $this->properties = $properties;
    }

    /**
     * Wrap incomming items.
     */
    public static function wrap($items): DatasourceResult
    {
        if ($items instanceof DatasourceResult) {
            return $items;
        }

        return new self($items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        if ($this->items instanceof \Closure) {
            return \call_user_func($this->items);
        }

        if (\is_array($this->items)) {
            return new \ArrayIterator($this->items);
        }

        if (\is_iterable($this->items)) {
            return $this->items;
        }

        return new \EmptyIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null !== $this->count) {
            return $this->count;
        }

        if (\is_countable($this->items)) {
            return $this->count = \count($this->items);
        }

        return $this->count = 0;
    }
}
