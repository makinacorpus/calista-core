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
            'extra' => [],
            'properties' => null,
            'renderer' => '',
            'show_filters' => true,
            'show_pager' => true,
            'show_sort' => true,
        ]);

        $resolver->setRequired('renderer');

        $resolver->setAllowedTypes('enabled_filters', ['null', 'array']);
        $resolver->setAllowedTypes('extra', ['array']);
        $resolver->setAllowedTypes('properties', ['null', 'array']);
        $resolver->setAllowedTypes('renderer', ['string', ViewRenderer::class]);
        $resolver->setAllowedTypes('show_filters', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_pager', ['numeric', 'bool']);
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
     * @return null|bool|array|PropertyView
     */
    public function getProperty(string $name)
    {
        return $this->options['properties'][$name] ?? null;
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
        return null === $this->options['properties'] || ($this->options['properties'][$name] ?? false);
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
