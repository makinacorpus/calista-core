<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * Use this as base class for your filters, it'll help.
 */
abstract class AbstractFilter implements Filter
{
    private array $attributes = [];
    private ?string $description = null;
    private bool $isSafe = false;
    private bool $mandatory = false;
    private bool $multiple = true;
    private ?string $queryParameter = null;
    private ?string $title = null;
    private ?string $propertyName = null;

    public function __construct(string $queryParameter, ?string $title = null, ?string $description = null)
    {
        $this->description = $description;
        $this->queryParameter = $queryParameter;
        $this->title = $title;
        $this->propertyName = $queryParameter;
    }

    /**
     * {@inheritdoc}
     */
    final public function setAttribute(string $attributeName, ?string $value): self
    {
        $this->attributes[$attributeName] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function getAttribute(string $attributeName, ?string $default = null): ?string
    {
        return $this->attributes[$attributeName] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    final public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    final public function setMultiple(bool $toggle = true): self
    {
        $this->multiple = $toggle;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * {@inheritdoc}
     */
    final public function setMandatory(bool $toggle = true): self
    {
        $this->mandatory = (bool)$toggle;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * {@inheritdoc}
     */
    public function getNoneOption(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasChoices(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getChoicesMap(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    final public function getTitle(): string
    {
        if (!$this->title) {
            return $this->queryParameter ?? '';
        }

        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    final public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    final public function getFilterName(): string
    {
        return $this->queryParameter ?? '';
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    final public function getField(): string
    {
        return $this->queryParameter ?? '';
    }

    /**
     * {@inheritdoc}
     */
    final public function setPropertyName(string $propertyName): self
    {
        $this->propertyName = $propertyName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    /**
     * {@inheritdoc}
     */
    final public function getSelectedValues(Query $query): array
    {
        return (array)$query->get($this->queryParameter);
    }

    /**
     * Get query parameters for a singe link.
     */
    final protected function getParametersForLink(Query $query, RouteHolder $routeHolder, string $value, bool $remove = false): array
    {
        $additional = $query->toArray();
        $selectedValues = $this->getSelectedValues($query);

        if ($remove) {
            while (false !== ($pos = \array_search($value, $selectedValues))) {
                unset($selectedValues[$pos]);
            }
        } else {
            if (false === \array_search($value, $selectedValues)) {
                $selectedValues[] = $value;
            }
        }

        $additional[$this->queryParameter] = $selectedValues;

        return $routeHolder->getRouteParameters($additional);
    }

    /**
     * {@inheritdoc}
     */
    final public function isSafe(): bool
    {
        return $this->isSafe;
    }
}
