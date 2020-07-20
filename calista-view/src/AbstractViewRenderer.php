<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Query;

/**
 * Boilerplate code for view implementations.
 */
abstract class AbstractViewRenderer implements ViewRenderer
{
    /**
     * Aggregate properties from the ViewDefinition
     *
     * @todo this method is ugly and needs cleanup, but at least it is well tested;
     *   it MUST NOT be more complex, it should be split in smaller pieces!
     *
     * @param ViewDefinition $viewDefinition
     * @param DatasourceResultInterface $items
     *
     * @return PropertyView[]
     */
    protected function normalizeProperties(ViewDefinition $viewDefinition, DatasourceResultInterface $items): array
    {
        $ret = [];

        $definitions = [];

        // First attempt to fetch arbitrary list of properties given by the page
        // definition or view configuration
        $properties = $viewDefinition->getDisplayedProperties();

        // If nothing was given, use the properties the datasource result
        // interface may carry, attention thought, the returned objects are
        // no string and must be normalized, hence the $definition array that
        // will be re-used later
        if (!$properties) {
            $properties = [];
            foreach ($items->getProperties() as $definition) {
                $name = $definition->getName();
                $definitions[$name]= $definition;
                $properties[] = $name;
            }
        }

        // The property info extractor might return null if nothing was found
        if (!$properties) {
            $properties = [];
        }

        foreach ($properties as $name) {
            // $name can be numeric, if you have a datasource returning rows
            // as arrays, such as the CSV datasource.
            $name = (string)$name;

            if (!$viewDefinition->isPropertyDisplayed($name)) {
                continue;
            }

            $options = $viewDefinition->getPropertyDisplayOptions($name);

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

    /**
     * {@inheritdoc}
     */
    public function renderInStream(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, $resource): void
    {
        \trigger_error(\sprintf("%s::%s uses default slow implementation, consider implementing it", static::class, __METHOD__), E_USER_NOTICE);

        if (!\is_resource($resource)) {
            throw new \InvalidArgumentException("Given \$resource argument is not a resource");
        }

        if (false === \fwrite($resource, $this->render($viewDefinition, $items, $query))) {
            throw new \RuntimeException("Could not write in stream");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderInFile(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, string $filename): void
    {
        if (\file_exists($filename) && 0 !== \filesize($filename)) {
            throw new \InvalidArgumentException(\sprintf("'%s' not overwrite existing file", $filename));
        }
        try {
            if (!$resource = \fopen($filename, "wb+")) {
                throw new \InvalidArgumentException(\sprintf("'%s' could not open file for writing", $filename));
            }
            $this->renderInStream($viewDefinition, $items, $query, $resource);
        } finally {
            if ($resource) {
                @\fclose($resource);
            }
        }
    }
}
