<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * Default implementation that will convert a single hashmap to a set of links.
 */
class Filter implements \Countable
{
    private bool $arbitraryInput = false;
    private bool $asLinks = false;
    private array $attributes = [];
    private bool $boolean = false;
    private array $choicesMap = [];
    private string $dateFormat = 'd/m/Y';
    private ?string $description = null;
    private bool $isDate = false;
    private bool $isSafe = false;
    private bool $mandatory = false;
    private bool $multiple = true;
    private ?string $noneOption = null;
    private ?string $queryParameter = null;
    private ?string $title = null;

    public function __construct(string $queryParameter, ?string $title = null, ?string $description = null)
    {
        $this->description = $description;
        $this->queryParameter = $queryParameter;
        $this->title = $title;
    }

    /**
     * Set the "boolean" toggle.
     */
    public function setBoolean(bool $toggle = true): self
    {
        $this->boolean = (bool)$toggle;

        return $this;
    }

    /**
     * Is boolean.
     */
    public function isBoolean(): bool
    {
        return $this->boolean && !$this->arbitraryInput && !$this->choicesMap;
    }

    /**
     * Set the "date" mode.
     */
    public function setIsDate(bool $toggle): self
    {
        $this->isDate = $toggle;

        return $this;
    }

    /**
     * This filter represents a date.
     */
    public function isDate(): bool
    {
        return $this->isDate;
    }

    /**
     * Set arbitrary attributes over the widget.
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get arbitrary attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set or unset the "multiple" flag, default is true.
     */
    public function setMultiple(bool $toggle = true): self
    {
        $this->multiple = $toggle;

        return $this;
    }

    /**
     * Does this filter allows multiple input.
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Set the "None/All/N/A" option.
     */
    public function setNoneOption(?string $value): self
    {
        $this->noneOption = $value;

        return $this;
    }

    /**
     * Get the none option.
     */
    public function getNoneOption(): ?string
    {
        return $this->noneOption;
    }

    /**
     * Set or unset the mandatory flag.
     */
    public function setMandatory(bool $toggle = true): self
    {
        $this->mandatory = (bool)$toggle;

        return $this;
    }

    /**
     * Is this filter mandatory.
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * Is arbitrary input field.
     */
    public function isArbitraryInput(): bool
    {
        return !$this->choicesMap && $this->arbitraryInput;
    }

    /**
     * Set or unset the arbitrary input flag.
     */
    public function setArbitraryInput(bool $toggle = true): self
    {
        $this->arbitraryInput = $toggle;

        return $this;
    }

    /**
     * If this returns false, use form checkboxes or assimilate, else just write links.
     */
    public function isAsLinks(): bool
    {
        return $this->asLinks;
    }

    /**
     * As facet like links instead of form checkbox.
     */
    public function setAsLinks(bool $toggle = true): self
    {
        $this->asLinks = $toggle;

        return $this;
    }

    /**
     * Set choices map.
     *
     * Choice map is a key-value array in which keys are indexed values and
     * values are human readable names that will supplant the indexed values
     * for end-user display, this has no effect on the query.
     *
     * @param string[] $choicesMap
     *   Keys are filter value, values are human readable labels.
     *
     * @return $this
     */
    public function setChoicesMap(array $choicesMap): self
    {
        $this->isSafe = true;
        $this->choicesMap = $choicesMap;

        return $this;
    }

    /**
     * Has this filter choices.
     */
    public function hasChoices(): bool
    {
        return !empty($this->choicesMap);
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        if (!$this->title) {
            return $this->queryParameter ?? '';
        }

        return $this->title;
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Remove selected choices.
     */
    public function removeChoices(array $choices): void
    {
        $this->choicesMap = \array_diff_key($this->choicesMap, \array_flip($choices));
    }

    /**
     * Remove selected choices.
     */
    public function removeChoicesNotIn(array $choices): void
    {
        $this->choicesMap = \array_intersect_key($this->choicesMap, \array_flip($choices));
    }

    /**
     * Get field.
     */
    public function getField(): string
    {
        return $this->queryParameter ?? '';
    }

    /**
     * Get selected values from query.
     */
    public function getSelectedValues(Query $query): array
    {
        return (array)$query->get($this->queryParameter);
    }

    /**
     * Get query parameters for a singe link.
     */
    private function getParametersForLink(Query $query, RouteHolder $routeHolder, string $value, bool $remove = false): array
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
     * Get links.
     *
     * @param Query $query
     * @param RouteHolder $routeHolder
     *   Usually, you would pass the View object here when using this method.
     *
     * @return Link[]
     */
    public function getLinks(Query $query, RouteHolder $routeHolder): array
    {
        $ret = [];

        $route = $routeHolder->getRoute();
        $selectedValues = $this->getSelectedValues($query);

        foreach ($this->choicesMap as $value => $label) {
            $isActive = \in_array($value, $selectedValues);

            if ($isActive) {
                $linkQuery = $this->getParametersForLink($query, $routeHolder, (string)$value, true);
            } else {
                $linkQuery = $this->getParametersForLink($query, $routeHolder, (string)$value);
            }

            $ret[] = new Link($label, $route, $linkQuery, null, $isActive);
        }

        return $ret;
    }

    /**
     * For the view build process, return the complete allowed choices map
     * accompanied with the selected state.
     *
     * @return FilterValue[]
     */
    public function getChoicesState(Query $query): array
    {
        $ret = [];

        $query = $query->all();
        $selectedValues = $this->getSelectedValues($query);

        foreach ($this->choicesMap as $value => $label) {
            $ret[] = new FilterValue($value, $label, \in_array($value, $selectedValues));
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->choicesMap);
    }

    public function isSafe(): bool
    {
        return $this->isSafe;
    }

    public function getChoicesMap(): array
    {
        return $this->choicesMap;
    }
}
