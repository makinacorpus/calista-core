<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceResult;
use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\Query\RouteHolder;
use MakinaCorpus\Calista\Query\RouteHolderTrait;

/**
 * Simple data transport object that ties datasource and input definition
 * and view definition altogether.
 *
 * For building complex UI, this will also hold the route for generating filter
 * form parameter names and sort links URLs.
 */
final class View implements RouteHolder
{
    use RouteHolderTrait;

    private ViewDefinition $definition;
    private DatasourceResult $items;
    private Query $query;
    private ?array $normalizedProperties = null;

    /**
     * @param null|array|ViewDefinition $definition
     * @param iterable|callable|DatasourceResult $items
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
     * @param iterable|callable|DatasourceResult $items
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

    public function getResult(): DatasourceResult
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

        foreach ($properties as $propertyName) {
            // $propertyName can be numeric.
            $propertyName = (string)$propertyName;

            if (!$this->definition->isPropertyDisplayed($propertyName)) {
                continue;
            }

            $value = $this->definition->getProperty($propertyName);

            if ($value instanceof PropertyDescription) {
                $ret[] = $value;
            } else if ($value instanceof PropertyView) {
                $ret[] = $value;
            } else if (\is_array($value)) {
                $ret[] = new PropertyView($propertyName, $value['type'] ?? null, $value);
            } else {
                $ret[] = new PropertyView($propertyName);
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

            $propertyName = $description->getName();

            $ret[$propertyName] = PropertyView::fromDescription(
                $description,
                $this->definition->getPropertyDisplayOptions($propertyName)
            );
        }

        return $ret;
    }
}
