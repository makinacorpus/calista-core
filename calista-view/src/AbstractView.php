<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

/**
 * Boilerplate code for view implementations.
 */
abstract class AbstractView implements ViewInterface
{
    private $propertyInfoExtractor;

    public function setPropertyInfoExtractor(PropertyInfoExtractorInterface $propertyInfoExtractor)
    {
        $this->propertyInfoExtractor = $propertyInfoExtractor;
    }

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

        $class = $items->getItemClass();
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

        // Last resort options, if nothing was found attempt using the property
        // info component, we do it last not because it's not accurate, in the
        // opposite, but because it's definitely the slowest one
        if (!$properties) {
            if ($this->propertyInfoExtractor) {
                $properties = $this->propertyInfoExtractor->getProperties($class);
            }
        }

        // The property info extractor might return null if nothing was found
        if (!$properties) {
            $properties = [];
        }

        foreach ($properties as $name) {
            if (!$viewDefinition->isPropertyDisplayed($name)) {
                continue;
            }

            $type = null;
            $options = $viewDefinition->getPropertyDisplayOptions($name);

            if (isset($definitions[$name])) {
                $options += [
                    'label' => $definitions[$name]->getLabel(),
                    'type' => $definitions[$name]->getType(),
                ] + $definitions[$name]->getDefaultDisplayOptions();
            }

            if (empty($options['label'])) {
                if ($this->propertyInfoExtractor) {
                    $options['label'] = $this->propertyInfoExtractor->getShortDescription($class, $name);
                }
                // Property info component might still return null here, give
                // the user a sensible fallback
                if (empty($options['label'])) {
                    $options['label'] = $name;
                }
            }

            // Determine data type from whatever we can find, still type can be
            // enforced by the user, at his own risks
            if (!empty($options['type'])) {
                $type = TypeHelper::getTypeInstance($options['type']);

            } else if ($this->propertyInfoExtractor) {
                $types = $this->propertyInfoExtractor->getTypes($class, $name);

                if ($types) {
                    if (\is_array($types)) {
                        $type = \reset($types);
                    } else {
                        $type = $types;
                    }
                }
            }

            $ret[$name] = new PropertyView($name, $type, $options);
        }

        return $ret;
    }
}
