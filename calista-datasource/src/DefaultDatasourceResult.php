<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

/**
 * Basics for the datasource result interface implementation.
 */
class DefaultDatasourceResult implements \IteratorAggregate, DatasourceResultInterface
{
    use DatasourceResultTrait;

    private iterable $items;
    private ?int $count = null;

    /**
     * Default constructor
     *
     * @param iterable|callable $items
     * @param array PropertyDescription[]
     */
    public function __construct($items = [], array $properties = [])
    {
        if (!\is_iterable($items) && !\is_callable($items)) {
            throw new \InvalidArgumentException("Items must be iterable or callable");
        }

        $this->items = $items;

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
    public static function wrap($items): DatasourceResultInterface
    {
        if ($items instanceof DatasourceResultInterface) {
            return $items;
        }

        return new self($items);
    }

    /**
     * {@inheritdoc}
     */
    public function canStream(): bool
    {
        // Having an array here would mean data has been preloaded hence it is
        // not gracefully streamed from the real datasource.
        return !\is_array($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
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
