<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\Query\Query;

/**
 * View definition holder and normalizer.
 */
final class View
{
    private ViewDefinition $definition;
    private DatasourceResultInterface $items;
    private Query $query;
    private ?array $normalizedProperties = null;

    /**
     * @param null|array|ViewDefinition $definition
     * @param iterable|callable|DatasourceResultInterface $items
     */
    public function __construct($definition, $items, ?Query $query = null)
    {
        $this->definition = ViewDefinition::wrap($definition ?? []);
        $this->items = DefaultDatasourceResult::wrap($items);
        $this->query = $query ?? Query::empty();
    }

    /**
     * Create arbitrary instance from given items.
     *
     * @param iterable|callable|DatasourceResultInterface $items
     */
    public static function createFromItems($items): self
    {
        return new self(ViewDefinition::empty(), $items);
    }

    /**
     * Create empty instance.
     */
    public static function empty(): self
    {
        return new self(ViewDefinition::empty(), []);
    }

    public function getDefinition(): ViewDefinition
    {
        return $this->definition;
    }

    public function getResult(): DatasourceResultInterface
    {
        return $this->items;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return PropertyView[]
     */
    public function getNormalizedProperties(): array
    {
        return $this->normalizedProperties ?? ($this->normalizedProperties = $this->normalizeProperties());
    }

    /**
     * Aggregate properties from the view definition.
     *
     * @todo
     *    - test property view object, dealing with properties
     *    - test this method throughoutly
     *
     * @return PropertyView[]
     */
    private function normalizeProperties(): array
    {
        $ret = [];

        $properties = $this->definition->getDisplayedProperties();

        // If nothing was given, use the properties the datasource result
        // interface may carry, attention thought, the returned objects are
        // no string and must be normalized, hence the $definition array that
        // will be re-used later
        if (!$properties) {
            return $this->normalizePropertiesUsingDatasource();
        }

        foreach ($properties as $name) {
            // $name can be numeric.
            $name = (string)$name;

            if (!$this->definition->isPropertyDisplayed($name)) {
                continue;
            }

            $value = $this->definition->getProperty($name);

            if ($value instanceof PropertyDescription) {
                $ret[] = $value;
            } else if ($value instanceof PropertyView) {
                $ret[] = $value;
            } else if (\is_array($value)) {
                $ret[] = new PropertyView($name, $value['type'] ?? null, $value);
            } else {
                $ret[] = new PropertyView($name);
            }
        }

        return $ret;
    }

    /**
     * @return PropertyView[]
     */
    private function normalizePropertiesUsingDatasource(): array
    {
        $ret = [];

        foreach ($this->items->getProperties() as $description) {
            \assert($description instanceof PropertyDescription);

            $name = $description->getName();

            $ret[$name] = PropertyView::fromDescription(
                $description,
                $this->definition->getPropertyDisplayOptions($name)
            );
        }

        return $ret;
    }
}
