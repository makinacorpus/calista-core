<?php

namespace MakinaCorpus\Calista\Datasource;

/**
 * Represents a single property as defined by a datasource
 *
 * All this object properties referes to the PropertyView available options
 *
 * @see \MakinaCorpus\Calista\View\PropertyView
 */
class PropertyDescription
{
    private $defaultDisplayOptions = [];
    private $label;
    private $name;
    private $type;

    /**
     * Default constructor
     *
     * @param string $name
     *   Datasource item property name
     * @param string $label
     *   Human readable label
     * @param string $type
     *   Valid class name or PHP internal type
     * @param array $defaultDisplayOptions
     *   Default view options for this property
     */
    public function __construct(string $name, string $label = '', string $type = '', array $defaultViewOptions = [])
    {
        $this->name = $name ?? '';
        $this->label = $label ?? '';
        $this->type = $type ?? '';
        $this->defaultDisplayOptions = $defaultViewOptions ?? [];
    }

    /**
     * Get datasource item property name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get human readable label
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get property PHP class or PHP internal type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get default display options for this property
     */
    public function getDefaultDisplayOptions(): array
    {
        return $this->defaultDisplayOptions;
    }
}
