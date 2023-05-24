<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

/**
 * Represents a single property as defined by a datasource.
 *
 * All this object properties referes to the PropertyView available options.
 *
 * @see \MakinaCorpus\Calista\View\PropertyView
 */
class PropertyDescription
{
    private array $defaultViewOptions = [];
    private string $name;
    private ?string $label = null;
    private ?string $type = null;

    /**
     * Default constructor.
     *
     * @param string $name
     *   Datasource item property name.
     * @param string $label
     *   Human readable label.
     * @param string $type
     *   Valid class name or PHP internal type.
     * @param array $defaultViewOptions
     *   Default view options for this property.
     */
    public function __construct(string $name, ?string $label = null, ?string $type = null, array $defaultViewOptions = [])
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->defaultViewOptions = $defaultViewOptions;
    }

    /**
     * Create clone with new name.
     *
     * @param string $label
     *   If set to null, label will be unchanged.
     */
    public function rename(string $newName, ?string $label = null, array $optionsOverrides = []): static
    {
        $ret = clone $this;
        $ret->defaultViewOptions = $optionsOverrides + $this->defaultViewOptions;
        $ret->name = $newName;
        $ret->label = $label ?? $this->label;

        return $ret;
    }

    /**
     * Get datasource item property name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get human readable label.
     */
    public function getLabel(): string
    {
        return $this->label ?? $this->name;
    }

    /**
     * Get property PHP class or PHP internal type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get default display options for this property.
     */
    public function getDefaultViewOptions(): array
    {
        return $this->defaultViewOptions;
    }
}
