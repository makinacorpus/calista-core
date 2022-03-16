<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * Filter for view display.
 *
 * Filter can be implemented in custom code, as long as you implement this
 * interface correctly, fact is, you should always extend the AbstractFilter
 * which will give you all the basics.
 *
 * Once you implemented a filter, you just extend AbstractFilter and let your
 * class empty, that's fine, you need to implement its template view.
 *
 * A filter has a getTemplateBlockSuffix() method, which will result in your
 * widget being rendered using the {% calista_filter_SUFFIX %} twig block.
 * This block will inherit from all values that are being used at render time
 * in the main calista main, includes 'query', 'result' and others.
 *
 * Per default, the 'default' filter will be rendered in the main template file
 * 'page.html.twig' you can find in this package. For custom filters, your must
 * create a new file in your project, and add your own filter blocks within,
 * just like symfony/form does, then register it into configuration under
 * the 'calista: filter_themes: []' array entry.
 *
 * Remember, when implementing a custom filter, that only one value can be
 * sent and will be dealt with the Query object using the InputDefinition
 * object: you may use any kind of HTML <input> type with the corresponding
 * name="{{ filter.getField() }}", this is the value that will be validated
 * against and set in the Query object. It can be pretty much everything
 * including <input type="hidden"/> types, so you're free here to do whatever
 * you want, including adding complex front code to populate this value.
 */
interface Filter
{
    /**
     * Set a single attribute value.
     */
    public function setAttribute(string $name, ?string $value): self;

    /**
     * Set arbitrary attributes over the widget.
     */
    public function setAttributes(array $attributes): self;

    /**
     * Get a single attribute value.
     */
    public function getAttribute(string $name, ?string $default = null): ?string;

    /**
     * Get arbitrary attributes.
     */
    public function getAttributes(): array;

    /**
     * Set or unset the "multiple" flag, default is true.
     */
    public function setMultiple(bool $toggle = true): self;

    /**
     * Does this filter allows multiple input.
     */
    public function isMultiple(): bool;

    /**
     * Set or unset the mandatory flag.
     */
    public function setMandatory(bool $toggle = true): self;

    /**
     * Is this filter mandatory.
     */
    public function isMandatory(): bool;

    /**
     * Get the none option.
     */
    public function getNoneOption(): ?string;

    /**
     * Has this filter choices.
     */
    public function hasChoices(): bool;

    /**
     * Get choice map.
     */
    public function getChoicesMap(): array;

    /**
     * Get title.
     */
    public function getTitle(): string;

    /**
     * Get description.
     */
    public function getDescription(): ?string;

    /**
     * Get field.
     */
    public function getField(): string;

    /**
     * Get selected values from query.
     */
    public function getSelectedValues(Query $query): array;

    /**
     * Is safe, for what... Well that's awkward I don't remember...
     */
    public function isSafe(): bool;

    /**
     * Get template twig block suffix, resulting block must be named after
     * it: {% block calista_filter_SUFFIX %}.
     */
    public function getTemplateBlockSuffix(): string;
}
