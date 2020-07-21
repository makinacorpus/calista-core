<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * View definition sanitizer.
 *
 * Define the generic behavior of views. Not all implementations will react on
 * all options.
 *
 * Available options are:
 *
 *  - enabled_filters: it can be either null, which literally means that you want
 *    to display ALL filters, or an array of available filters for this view
 *    case in which each value must be a known filter identifier. The array is
 *    ordered and order will be replicated in display.
 *
 *  - properties: an array of item property (columns) to display if you are
 *    using a dynamic view implementation. Each keys of this array is a known
 *    property name, and value can be either:
 *
 *      - false: do not display this property
 *      - true: display this property with default options
 *      - array: display this property with the given options
 *      - callable: use this callable to display the property, callable MUST
 *        accept the value as the first argument, all other argumenst MUST be
 *        OPTIONAL
 *
 *    Please note that non existing properties will not make this options
 *    resolver throw any error, it's up to the view implementation to ignore
 *    them.
 *
 *  - show_filters: if set to false, no filters will be displayed at all.
 *  - show_pager: if set to false, pager if enabled will not be displayed.
 *  - show_search: if set to false, search bar if enabled will not be displayed.
 *  - show_sort: if set to false, sort links will not be displayed.
 *
 *  - view_type: class name or service identifier of the view implementation to
 *    use which will do the rendering and to which this ViewDefinition instance
 *    will be given to.
 *
 * @codeCoverageIgnore
 */
class ViewDefinition
{
    private array $allowedFilters = [];
    private array $allowedSorts = [];
    private array $options = [];

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public static function empty(): self
    {
        return new self(['view_type' => '']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'enabled_filters' => null,
            'extra' => [],
            'properties' => null,
            'show_filters' => true,
            'show_pager' => true,
            'show_search' => true,
            'show_sort' => true,
            'view_type' => '',
        ]);

        $resolver->setRequired('view_type');

        $resolver->setAllowedTypes('enabled_filters', ['null', 'array']);
        $resolver->setAllowedTypes('extra', ['array']);
        $resolver->setAllowedTypes('properties', ['null', 'array']);
        $resolver->setAllowedTypes('show_filters', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_pager', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_search', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_sort', ['numeric', 'bool']);
        $resolver->setAllowedTypes('view_type', ['string', ViewRenderer::class]);
    }

    /**
     * Get extra options.
     *
     * Extra options are backend specific options, they should be validated by
     * the view implementation itself.
     *
     * @return array
     */
    public function getExtraOptions(): array
    {
        return $this->options['extra'] ?? [];
    }

    /**
     * Get extra option value.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getExtraOptionValue(string $name, $default = null)
    {
        return \array_key_exists($name, $this->options['extra']) ? $this->options['extra'][$name] : $default;
    }

    /**
     * Get displayed properties.
     *
     * @return null|string[]
     *   Null means display everything
     */
    public function getDisplayedProperties(): ?array
    {
        if (!\is_array($this->options['properties'])) {
            return null;
        }

        return \array_keys($this->options['properties']);
    }

    /**
     * Get property specific display options.
     */
    public function getPropertyDisplayOptions(string $name): array
    {
         if (isset($this->options['properties'][$name])) {
              if (\is_string($this->options['properties'][$name])) {
                  return ['type' => $this->options['properties'][$name]];
              } else if (\is_array($this->options['properties'][$name])) {
                  return $this->options['properties'][$name];
              }
         }

         return [];
    }

    /**
     * Should this property be displayed.
     */
    public function isPropertyDisplayed(string $name): bool
    {
        return null === $this->options['properties'] || (isset($this->options['properties'][$name]) && false !== $this->options['properties'][$name]);
    }

    /**
     * Get enabled filters.
     *
     * @return null|string[]
     *   Null means enable everything.
     */
    public function getEnabledFilters(): array
    {
        return $this->isFiltersEnabled() ? $this->options['enabled_filters'] : [];
    }

    /**
     * Are filters enabled.
     */
    public function isFilterDisplayed(string $name): bool
    {
        return $this->isFiltersEnabled() && (null === $this->options['enabled_filters'] || \in_array($name, $this->options['enabled_filters']));
    }

    /**
     * Is filters enabled.
     */
    public function isFiltersEnabled(): bool
    {
        return $this->options['show_filters'] ?? false;
    }

    /**
     * Is search bar enabled.
     */
    public function isSearchEnabled(): bool
    {
        return $this->options['show_search'] ?? false;
    }

    /**
     * Is sort enabled.
     */
    public function isSortEnabled(): bool
    {
        return $this->options['show_sort'] ?? false;
    }

    /**
     * Is pager enabled.
     */
    public function isPagerEnabled(): bool
    {
        return $this->options['show_pager'] ?? false;
    }

    /**
     * Get view type.
     */
    public function getViewType(): string
    {
        return $this->options['view_type'];
    }
}
