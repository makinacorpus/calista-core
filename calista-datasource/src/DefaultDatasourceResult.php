<?php

namespace MakinaCorpus\Calista\Datasource;

/**
 * Basics for the datasource result interface implementation
 */
class DefaultDatasourceResult implements \IteratorAggregate, DatasourceResultInterface
{
    use DatasourceResultTrait;

    private $items;
    private $count;

    /**
     * Default constructor
     *
     * @param string $itemClass
     * @param iterable $items
     * @param array PropertyDescription[]
     */
    public function __construct(string $itemClass = '', $items = [], array $properties = [])
    {
        $this->itemClass = $itemClass;
        $this->items = $items;

        foreach ($properties as $index => $property) {
            if (!$property instanceof PropertyDescription) {
                throw new \InvalidArgumentException(sprintf("property at index %s is not a %s instance", $index, PropertyDescription::class));
            }
        }

        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function canStream(): bool
    {
        // Having an array here would mean data has been preloaded hence it is
        // not gracefully streamed from the real datasource.
        return !is_array($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if ($this->items instanceof \Closure) {
            return call_user_func($this->items);
        }

        if ($this->items instanceof \Traversable || $this->items instanceof \Generator) {
            return $this->items;
        }

        if (is_array($this->items)) {
            return new \ArrayIterator($this->items);
        }

        return new \EmptyIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (null !== $this->count) {
            return $this->count;
        }

        if (is_array($this->items) || $this->items instanceof \Countable) {
            return $this->count = count($this->items);
        }

        return $this->count = 0;
    }
}
