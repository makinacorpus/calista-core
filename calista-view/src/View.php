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
     * @todo this method is ugly and needs cleanup, but at least it is well tested;
     *   it MUST NOT be more complex, it should be split in smaller pieces!
     *
     * @return PropertyView[]
     */
    private function normalizeProperties(): array
    {
        $ret = [];

        $definitions = [];

        // First attempt to fetch arbitrary list of properties given by the page
        // definition or view configuration
        $properties = $this->definition->getDisplayedProperties();

        // If nothing was given, use the properties the datasource result
        // interface may carry, attention thought, the returned objects are
        // no string and must be normalized, hence the $definition array that
        // will be re-used later
        if (!$properties) {
            $properties = [];

            foreach ($this->items->getProperties() as $definition) {
                \assert($definition instanceof PropertyDescription);

                $name = $definition->getName();
                $definitions[$name]= $definition;
                $properties[] = $name;
            }
        }

        // The property info extractor might return null if nothing was found
        if (!$properties) {
            return [];
        }

        foreach ($properties as $name) {
            // $name can be numeric, if you have a datasource returning rows
            // as arrays, such as the CSV datasource.
            $name = (string)$name;

            if (!$this->definition->isPropertyDisplayed($name)) {
                continue;
            }

            $options = $this->definition->getPropertyDisplayOptions($name);

            if (isset($definitions[$name])) {
                $options += [
                    'label' => $definitions[$name]->getLabel(),
                    'type' => $definitions[$name]->getType(),
                ] + $definitions[$name]->getDefaultViewOptions();
            }

            $ret[$name] = new PropertyView((string)$name, $options['type'] ?? null, $options);
        }

        return $ret;
    }
}
