<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * Default implementation that will convert a single hashmap to a set of links
 */
class Filter implements \Countable
{
    private $arbitraryInput = false;
    private $choicesMap = [];
    private $isSafe = false;
    private $mandatory = false;
    private $multiple = true;
    private $noneOption;
    private $queryParameter;
    private $title;

    /**
     * Default constructor
     *
     * @param string $queryParameter
     *   Query parameter name
     * @param string $title
     *   Filter title
     */
    public function __construct(string $queryParameter, string $title = null)
    {
        $this->queryParameter = $queryParameter;
        $this->title = $title;
    }

    /**
     * Set or unset the "multiple" flag, default is true
     */
    public function setMultiple(bool $toggle = true): self
    {
        $this->multiple = $toggle;

        return $this;
    }

    /**
     * Does this filter allows multiple input
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Set the "None/All/N/A" option
     */
    public function setNoneOption(?string $value): self
    {
        $this->noneOption = $value;

        return $this;
    }

    /**
     * Get the none option
     */
    public function getNoneOption(): ?string
    {
        return $this->noneOption;
    }

    /**
     * Set or unset the mandatory flag
     */
    public function setMandatory(bool $toggle = true): self
    {
        $this->mandatory = (bool)$toggle;

        return $this;
    }

    /**
     * Is this filter mandatory
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * Is arbitrary input field
     */
    public function isArbitraryInput(): bool
    {
        return !$this->choicesMap && $this->arbitraryInput;
    }

    /**
     * Set or unset the arbitrary input flag
     *
     * @param bool $toggle
     *
     * @return self
     */
    public function setArbitraryInput(bool $toggle = true): self
    {
        $this->arbitraryInput = $toggle;

        return $this;
    }

    /**
     * Set choices map
     *
     * Choice map is a key-value array in which keys are indexed values and
     * values are human readable names that will supplant the indexed values
     * for end-user display, this has no effect on the query.
     *
     * @param string[] $choicesMap
     *   Keys are filter value, values are human readable labels
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
     * Has this filter choices
     */
    public function hasChoices(): bool
    {
        return !empty($this->choicesMap);
    }

    /**
     * Get title
     */
    public function getTitle(): string
    {
        if (!$this->title) {
            return $this->queryParameter ?? '';
        }

        return $this->title;
    }

    /**
     * Remove selected choices
     *
     * @param array $choices
     */
    public function removeChoices(array $choices): void
    {
        $this->choicesMap = \array_diff_key($this->choicesMap, \array_flip($choices));
    }

    /**
     * Remove selected choices
     */
    public function removeChoicesNotIn(array $choices): void
    {
        $this->choicesMap = \array_intersect_key($this->choicesMap, \array_flip($choices));
    }

    /**
     * Get field
     */
    public function getField(): string
    {
        return $this->queryParameter ?? '';
    }

    /**
     * Get selected values from query
     *
     * @param string[] $query
     *
     * @return string[]
     */
    private function getSelectedValues(array $query): array
    {
        $values = [];

        if (isset($query[$this->queryParameter])) {

            $values = $query[$this->queryParameter];

            if (!\is_array($values)) {
                if (false !== \strpos($values, Query::URL_VALUE_SEP)) {
                    $values = \explode(Query::URL_VALUE_SEP, $values);
                } else {
                    $values = [$values];
                }
            }
        }

        return \array_map('trim', $values);
    }

    /**
     * Get query parameters for a singe link
     *
     * @param string[] $query
     *   Contextual query that represents the current page state
     * @param string $value
     *   Value for the given link
     * @param boolean $remove
     *   Instead of adding the value, it must removed from the query
     *
     * @return string[]
     *   New query with value added or removed
     */
    private function getParametersForLink(array $query, string $value, bool $remove = false): array
    {
        if (isset($query[$this->queryParameter])) {
            if (\is_array($query[$this->queryParameter])) {
                $actual = $query[$this->queryParameter];
            } else {
                $actual = \explode(Query::URL_VALUE_SEP, $query[$this->queryParameter]);
            }
        } else {
            $actual = [];
        }

        if ($remove) {
            if (false !== ($pos = \array_search($value, $actual))) {
                unset($actual[$pos]);
            }
        } else {
            if (false === \array_search($value, $actual)) {
                $actual[] = $value;
            }
        }

        if (empty($actual)) {
            unset($query[$this->queryParameter]);
            return $query;
        } else {
            \sort($actual);
            return [$this->queryParameter => \implode(Query::URL_VALUE_SEP, $actual)] + $query;
        }
    }

    /**
     * Get links
     *
     * @param Query $query
     *
     * @return Link[]
     */
    public function getLinks(Query $query): array
    {
        $ret = [];

        $route = $query->getRoute();
        $query = $query->getRouteParameters();

        $selectedValues = $this->getSelectedValues($query);

        foreach ($this->choicesMap as $value => $label) {

            $isActive = \in_array($value, $selectedValues);

            if ($isActive) {
                $linkQuery = $this->getParametersForLink($query, $value, true);
            } else {
                $linkQuery = $this->getParametersForLink($query, $value);
            }

            $ret[] = new Link($label, $route, $linkQuery, null, $isActive);
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

