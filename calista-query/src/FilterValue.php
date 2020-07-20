<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * Filter value.
 */
final class FilterValue
{
    private ?string $label = null;
    private bool $selected = false;
    private ?string $value = null;

    public function __construct(?string $value, ?string $label = null, bool $selected = false)
    {
        $this->label = $label;
        $this->selected = $selected;
        $this->value = $value;
    }

    /**
     * Is selected in current query.
     */
    public function isSelected(): bool
    {
        return $this->selected;
    }

    /**
     * Get human readable label for display.
     */
    public function getLabel(): ?string
    {
        return $this->label ?? $this->value;
    }

    /**
     * Get value for query.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
