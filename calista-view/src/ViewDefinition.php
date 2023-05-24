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
 *  - show_sort: if set to false, sort links will not be displayed.
 *
 *  - renderer: class name or service identifier of the view implementation to
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
        // Backward compatibility fixes.
        $options = $this->fixOptionsBackwardCompatibility($options);

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public static function empty(): self
    {
        return new self(['renderer' => '']);
    }

    public static function wrap($data): self
    {
        if ($data instanceof self) {
            return $data;
        }
        if (!\is_array($data)) {
            throw new \InvalidArgumentException("\$data must be either an array of options, or an instanceof %s", self::class);
        }

        return new self($data);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'enabled_filters' => null,
            // Extra is an arbitrary array of values that will be passed and
            // consumed at the discretion of the view renderer. It can be used
            // by specialized view renderers to pass rendering options.
            'extra' => [],
            // Preload allows the user to give either an arbitrary array of
            // values that will be the same for each row, or a callback that
            // will accept the raw loaded item as first parameter and that
            // returns an array of computed values for the row.
            // This allows to compute multiple rows at once, the returned array
            // keys must be known property names, if unknown they will be
            // silently ignored.
            'preload' => null,
            // List of properties instances. Keys are properties names, values
            // are either:
            //   - PropertyView instances,
            //   - array of options for PropertyView constructor,
            //   - null for disabling property display (same as false),
            //   - false for disabling property display (same as null),
            //   - true for enabling property display with default options
            //     (same as using an empty array).
            'properties' => null,
            'renderer' => '',
            'show_filters' => true,
            'show_pager' => true,
            'show_go_to_page' => false,
            'show_sort' => true,
        ]);

        $resolver->setRequired('renderer');

        $resolver->setAllowedTypes('enabled_filters', ['null', 'array']);
        $resolver->setAllowedTypes('extra', ['array']);
        $resolver->setAllowedTypes('preload', ['null', 'array', 'callable']);
        $resolver->setAllowedTypes('properties', ['null', 'array']);
        $resolver->setAllowedTypes('renderer', ['string', ViewRenderer::class]);
        $resolver->setAllowedTypes('show_filters', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_pager', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_go_to_page', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_sort', ['numeric', 'bool']);
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
     */
    public function getExtraOptionValue(string $name, $default = null): mixed
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
     * @return null|bool|array|PropertyView
     */
    public function getProperty(string $propertyName)
    {
        return $this->options['properties'][$propertyName] ?? null;
    }

    /**
     * Get property specific display options.
     */
    public function getPropertyDisplayOptions(string $propertyName): array
    {
         if (isset($this->options['properties'][$propertyName])) {
              if (\is_string($this->options['properties'][$propertyName])) {
                  return ['type' => $this->options['properties'][$propertyName]];
              } else if (\is_array($this->options['properties'][$propertyName])) {
                  return $this->options['properties'][$propertyName];
              }
         }

         return [];
    }

    /**
     * Should this property be displayed.
     */
    public function isPropertyDisplayed(string $propertyName): bool
    {
        return null === $this->options['properties'] || ($this->options['properties'][$propertyName] ?? false);
    }

    /**
     * Get item values preloader.
     *
     * @return callable
     *   First argument of callable is the raw item being displayed, returned
     *   value is a key-value pair array whose keys are properties names and
     *   values are displayable values. Returned values will override the
     *   property renderer.
     */
    public function getPreloader(): callable
    {
        $preloader = $this->options['preload'] ?? null;

        if (null === $preloader) {
            return fn ($item) => [];
        }
        if (\is_array($preloader)) {
            return fn ($item) => $preloader;
        }
        if (\is_callable($preloader)) {
            return $preloader;
        }

        throw new \LogicException("Invalid preloader found.");
    }

    /**
     * Get enabled filters.
     *
     * @return string[]
     *   Empty means enable everything.
     */
    public function getEnabledFilters(): array
    {
        return $this->isFiltersEnabled() ? $this->options['enabled_filters'] : [];
    }

    /**
     * Is given filter enabled.
     *
     * A filter can be either disabled, which means it cannot be displayed
     * in any way, or hidden, case in which it can be displayed to use, but
     * is hidden per default.
     */
    public function isFilterEnabled(string $filterName): bool
    {
        return $this->isFiltersEnabled() && (null === $this->options['enabled_filters'] || \in_array($filterName, $this->options['enabled_filters']));
    }

    /**
     * Is filters enabled.
     */
    public function isFiltersEnabled(): bool
    {
        return $this->options['show_filters'] ?? false;
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
     * Is pager enabled.
     */
    public function isGoToPageFormEnabled(): bool
    {
        return $this->options['show_go_to_page'] ?? false;
    }

    /**
     * Get renderer name.
     */
    public function getRendererName(): string
    {
        return $this->options['renderer'];
    }

    /**
     * Option BC fixes.
     */
    private function fixOptionsBackwardCompatibility(array $options): array
    {
        if (isset($options['show_search'])) {
            @\trigger_error("Using 'show_search' is deprecated, directive is ignored.", E_USER_DEPRECATED);
            unset($options['show_search']);
        }
        if (isset($options['view_type'])) {
            @\trigger_error("Using 'view_type' is deprecated, please use 'renderer' instead.", E_USER_DEPRECATED);
            $options['renderer'] = $options['view_type'];
            unset($options['view_type']);
        }
        if (isset($options['templates'])) {
            if (1 < \count($options['templates'])) {
                throw new \InvalidArgumentException("Using an array in 'templates' is not allowed anymore, 1 page = 1 'template' now.");
            }
            @\trigger_error("Using 'templates' (array) is deprecated, please use 'extra:template' (string) instead.", E_USER_DEPRECATED);
            if (isset($options['templates']['default'])) {
                $options['extra']['template'] = $options['templates']['default'];
            } else {
                $options['extra']['template'] = \reset($options['templates']['default']);
            }
            unset($options['templates']);
        }
        return $options;
    }
}
