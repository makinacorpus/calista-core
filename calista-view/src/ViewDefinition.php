<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * View definition sanitizer
 *
 * Define the generic behavior of views. Not all implementations will react on
 * all options.
 *
 * Available options are:
 *
 *  - default_display: default display identifier, this MUST be defined as a key
 *    in the 'templates' array
 *
 *  - enabled_filters: it can be either null, which literally means that you want
 *    to display ALL filters, or an array of available filters for this view
 *    case in which each value must be a known filter identifier. The array is
 *    ordered and order will be replicated in display
 *    @todo implement it
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
 *  - show_filters: if set to false, no filters will be displayed at all
 *  - show_pager: if set to false, pager if enabled will not be displayed
 *  - show_search: if set to false, search bar if enabled will not be displayed
 *  - show_sort: if set to false, sort links will not be displayed
 *
 *  - templates: an array whose keys are display identifiers (see the
 *    'default_display' parameter) and whose values are template names. For Twig
 *    based renderers, template name must be a valid Twig template name in the
 *    current environment, for others, value may be business specific. If no
 *    'default_display' is provided, first one in this array will be used
 *    instead
 *
 *  - view_type: class name or service identifier of the view implementation to
 *    use which will do the rendering and to which this ViewDefinition instance
 *    will be given to
 *
 *
 * @codeCoverageIgnore
 */
class ViewDefinition
{
    private $allowedFilters = [];
    private $allowedSorts = [];
    private $options = [];

    /**
     * Build an instance from an array
     *
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        if ($this->options['default_display']) {
            if (!$this->options['templates']) {
                throw new \InvalidArgumentException(sprintf("default display '%s' is set but no templates are", $this->options['default_display']));
            }
            if (!isset($this->options['templates'][$this->options['default_display']])) {
                throw new \InvalidArgumentException(
                    sprintf("default display '%s' does not exists in templates '%s'",
                    $this->options['default_display'],
                    implode("', '", array_keys($this->options['templates']))
                ));
            }
        }
    }

    /**
     * InputDefinition option resolver
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'default_display'   => null,
            'enabled_filters'   => null,
            'extra'             => [],
            'properties'        => null,
            'show_filters'      => true,
            'show_pager'        => true,
            'show_search'       => true,
            'show_sort'         => true,
            'templates'         => [],
            'view_type'         => '',
        ]);

        $resolver->setRequired('view_type');

        $resolver->setAllowedTypes('default_display', ['null', 'string']);
        $resolver->setAllowedTypes('enabled_filters', ['null', 'array']);
        $resolver->setAllowedTypes('extra', ['array']);
        $resolver->setAllowedTypes('properties', ['null', 'array']);
        $resolver->setAllowedTypes('show_filters', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_pager', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_search', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_sort', ['numeric', 'bool']);
        $resolver->setAllowedTypes('templates', ['array']);
        $resolver->setAllowedTypes('view_type', ['string', ViewInterface::class]);
    }

    /**
     * Get extra options
     *
     * Extra options are backend specific options, they should be validated by
     * the view implementation itself
     *
     * @return array
     */
    public function getExtraOptions()
    {
        return $this->options['extra'];
    }

    /**
     * Get extra option value
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getExtraOptionValue($name, $default = null)
    {
        return array_key_exists($name, $this->options['extra']) ? $this->options['extra'][$name] : $default;
    }

    /**
     * Get default display
     *
     * @return null|string
     */
    public function getDefaultDisplay()
    {
        return $this->options['default_display'];
    }

    /**
     * Get displayed properties
     *
     * @return null|string[]
     *   Null means display everything
     */
    public function getDisplayedProperties()
    {
        if (!is_array($this->options['properties'])) {
            return null;
        }

        return array_keys($this->options['properties']);
    }

    /**
     * Get property specific display options
     *
     * @param string $name
     *
     * @return array
     */
    public function getPropertyDisplayOptions($name)
    {
         if (isset($this->options['properties'][$name])) {
              if (is_string($this->options['properties'][$name])) {
                  return ['type' => $this->options['properties'][$name]];
              } else if (is_array($this->options['properties'][$name])) {
                  return $this->options['properties'][$name];
              }
         }

         return [];
    }

    /**
     * Should this property be displayed
     *
     * @param string $name
     *
     * @return bool
     */
    public function isPropertyDisplayed($name)
    {
        return null === $this->options['properties'] || (isset($this->options['properties'][$name]) && false !== $this->options['properties'][$name]);
    }

    /**
     * Get enabled filters
     *
     * @return null|string[]
     *   Null means enable everything
     */
    public function getEnabledFilters()
    {
        return $this->isFiltersEnabled() ? $this->options['enabled_filters'] : [];
    }

    /**
     * Are filters enabled
     *
     * @param string $name
     *
     * @return bool
     */
    public function isFilterDisplayed($name)
    {
        return $this->isFiltersEnabled() && (null === $this->options['enabled_filters'] || in_array($name, $this->options['enabled_filters']));
    }

    /**
     * Is filters enabled
     *
     * @return bool
     */
    public function isFiltersEnabled()
    {
        return $this->options['show_filters'];
    }

    /**
     * Is search bar enabled
     *
     * @return bool
     */
    public function isSearchEnabled()
    {
        return $this->options['show_search'];
    }

    /**
     * Is sort enabled
     *
     * @return bool
     */
    public function isSortEnabled()
    {
        return $this->options['show_sort'];
    }

    /**
     * Is pager enabled
     *
     * @return bool
     */
    public function isPagerEnabled()
    {
        return $this->options['show_pager'];
    }

    /**
     * Get templates
     *
     * @return string[]
     *   Keys are display identifiers, values are template names
     */
    public function getTemplates()
    {
        return $this->options['templates'];
    }

    /**
     * Get view type
     *
     * @return string
     */
    public function getViewType()
    {
        return $this->options['view_type'];
    }
}
