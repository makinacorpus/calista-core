<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * Default implementation that covers all core use cases:
 *   - arbitrary text input,
 *   - choices select list,
 *   - date text input,
 *   - boolean checkbox.
 */
class DefaultFilter extends AbstractFilter
{
    private bool $arbitraryInput = false;
    private bool $asLinks = false;
    private bool $boolean = false;
    private array $choicesMap = [];
    private string $dateFormat = 'd/m/Y';
    private bool $isDate = false;
    private bool $isHidden = false;
    private ?string $noneOption = null;

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
    public function setHidden(bool $toggle = true): self
    {
        $this->isHidden = $toggle;

        return $this;
    }

    /**
     * This filter is hidden.
     */
    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    /**
     * Set the "date" mode.
     */
    public function setIsDate(bool $toggle = true): self
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
     * Set the "None/All/N/A" option.
     */
    public function setNoneOption(?string $value): self
    {
        $this->noneOption = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNoneOption(): ?string
    {
        return $this->noneOption;
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
     * {@inheritdoc}
     */
    public function hasChoices(): bool
    {
        return !empty($this->choicesMap);
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
    public function getChoicesMap(): array
    {
        return $this->choicesMap;
    }

    /**
     * If this returns false, use form checkboxes or assimilate, else just write links.
     */
    final public function isAsLinks(): bool
    {
        return $this->asLinks;
    }

    /**
     * As facet like links instead of form checkbox.
     */
    final public function setAsLinks(bool $toggle = true): self
    {
        $this->asLinks = $toggle;

        return $this;
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
    final public function getLinks(Query $query, RouteHolder $routeHolder): array
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
     * {@inheritdoc}
     */
    public function getTemplateBlockSuffix(): string
    {
        if ($this->isHidden) {
            return 'hidden';
        }
        if ($this->isBoolean()) {
            return 'boolean';
        }
        if ($this->isArbitraryInput()) {
            return 'input';
        }
        if ($this->isDate()) {
            return 'date';
        }
        return 'choices';
    }
}
