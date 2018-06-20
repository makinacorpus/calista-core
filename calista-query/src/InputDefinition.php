<?php

namespace MakinaCorpus\Calista\Query;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Input query definition and sanitizer
 *
 * @codeCoverageIgnore
 */
class InputDefinition
{
    private $filterLabels = [];
    private $filters = [];
    private $options = [];

    /**
     * Default constructor
     */
    public function __construct(array $options = [])
    {
        if (isset($options['filter_list'])) {
            $options['filter_list'] = $this->fixFilters($options['filter_list']);
        }
        if (isset($options['sort_allowed_list'])) {
            $options['sort_allowed_list'] = $this->fixAllowedSorts($options['sort_allowed_list']);
        }

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        // Normalize filters and sorts
        foreach ($this->options['filter_list'] as $filter) {
            $name = $filter->getField();
            // Filter out non allowed (outside of base query) filter choices
            if (isset($this->options['base_query'][$name])) {
                $choices = $this->options['base_query'][$name];
                if (!is_array($choices)) {
                    $choices = [$choices];
                }
                $filter->removeChoicesNotIn($choices);
            }
            $this->filterLabels[$name] = $filter->getTitle();
        }

        // Do a few consistency checks based upon the advertised capabilities
        $searchFields = $this->getSearchFields();
        if ($this->options['search_enable']) {
            if (!$this->options['search_parse'] && !$searchFields) {
                throw new \InvalidArgumentException("fulltext search is disabled and there is no search field set");
            }
            foreach ($searchFields as $name) {
                if (!$this->isFilterAllowed($name)) {
                    throw new \InvalidArgumentException(sprintf("'%s' search field is not an allowed filter", $name));
                }
            }
        }

        // Ensure given base query only contains legitimate field names
        if ($this->options['base_query']) {
            foreach (array_keys($this->options['base_query']) as $name) {
                if (!$this->isFilterAllowed($name)) {
                    throw new \InvalidArgumentException(sprintf("'%s' base query filter is not an allowed filter", $name));
                }
            }
        }

        // Set the default sort if none was given by the user, yell if user
        // gave one which is not supported
        if (empty($this->options['sort_default_field'])) {
            $this->options['sort_default_field'] = key($this->options['sort_allowed_list']);
        } else {
            if (!$this->isSortAllowed($this->options['sort_default_field'])) {
                throw new \InvalidArgumentException(sprintf("'%s' sort field is not an allowed sort field", $this->options['sort_default_field']));
            }
        }
    }

    /**
     * Convert in given array all values to Filter instance if they are not
     */
    private function fixFilters(array $values): array
    {
        if (!$values) {
            return [];
        }

        $ret = [];
        foreach ($values as $key => $value) {
            if ($value instanceof Filter) {
                $ret[] = $value;
            } else if (\is_numeric($key)) {
                $ret[] = new Filter($value);
            } else {
                $ret[] = new Filter($key, $value);
            }
        }

        return $ret;
    }

    /**
     * Convert given array to (id => name) pairs
     */
    private function fixAllowedSorts(array $values): array
    {
        if (!$values) {
            return [];
        }

        $ret = [];
        foreach ($values as $key => $value) {
            if (\is_numeric($key)) {
                $ret[$value] = $value;
            } else {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * Build options resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'base_query'          => [],
            'display_param'       => 'display',
            // Must be a list of \MakinaCorpus\Calista\Query\Filter
            //   or a list of Key/value pairs, each key is a field name
            //   and value is the human readable label
            'filter_list'         => [],
            'limit_allowed'       => false,
            'limit_default'       => Query::LIMIT_DEFAULT,
            'limit_param'         => 'limit',
            'pager_enable'        => true,
            'pager_param'         => 'page',
            'search_enable'       => false,
            'search_field'        => null,
            'search_param'        => 's',
            'search_parse'        => false,
            // Keys are field names, values are labels
            'sort_allowed_list'   => [],
            'sort_default_field'  => '',
            'sort_default_order'  => Query::SORT_DESC,
            'sort_field_param'    => 'st',
            'sort_order_param'    => 'by',
        ]);

        $resolver->setAllowedTypes('base_query', ['array']);
        $resolver->setAllowedTypes('display_param', ['string']);
        $resolver->setAllowedTypes('limit_allowed', ['numeric', 'bool']);
        $resolver->setAllowedTypes('limit_default', ['numeric']);
        $resolver->setAllowedTypes('limit_param', ['string']);
        $resolver->setAllowedTypes('pager_enable', ['numeric', 'bool']);
        $resolver->setAllowedTypes('pager_param', ['string']);
        $resolver->setAllowedTypes('search_enable', ['numeric', 'bool']);
        $resolver->setAllowedTypes('search_field', ['null', 'string', 'array']);
        $resolver->setAllowedTypes('search_param', ['string']);
        $resolver->setAllowedTypes('search_parse', ['numeric', 'bool']);
        $resolver->setAllowedTypes('sort_allowed_list', ['array']);
        $resolver->setAllowedTypes('sort_default_field', ['string']);
        $resolver->setAllowedTypes('sort_default_order', ['string']);
        $resolver->setAllowedTypes('sort_field_param', ['string']);
        $resolver->setAllowedTypes('sort_order_param', ['string']);
    }

    /**
     * Get base query
     *
     * @return string[]
     */
    public function getBaseQuery(): array
    {
        return $this->options['base_query'];
    }

    /**
     * Get allowed filterable field list
     *
     * @return string[]
     *   Keys are field name, values are human readable labels
     */
    public function getAllowedFilters(): array
    {
        return $this->filterLabels;
    }

    /**
     * Get filter instances
     *
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->options['filter_list'];
    }

    /**
     * Is the given filter field allowed
     */
    public function isFilterAllowed(string $name): bool
    {
        return isset($this->filterLabels[$name]);
    }

    /**
     * Get allowed sort field list
     *
     * @return string[]
     *   Keys are field name, values are human readable labels
     */
    public function getAllowedSorts(): array
    {
        return $this->options['sort_allowed_list'];
    }

    /**
     * Is the given sort field allowed
     */
    public function isSortAllowed(string $name): bool
    {
        return isset($this->options['sort_allowed_list'][$name]);
    }

    /**
     * Get display parameter name
     */
    public function getDisplayParameter(): string
    {
        return $this->options['display_param'];
    }

    /**
     * Can the query change the limit
     */
    public function isLimitAllowed(): bool
    {
        return $this->options['limit_allowed'];
    }

    /**
     * Get the default limit
     */
    public function getDefaultLimit(): int
    {
        return $this->options['limit_default'];
    }

    /**
     * Get the limit parameter name
     */
    public function getLimitParameter(): string
    {
        return $this->options['limit_param'];
    }

    /**
     * Is paging enabled
     */
    public function isPagerEnabled(): bool
    {
        return $this->options['pager_enable'];
    }

    /**
     * Get page parameter
     */
    public function getPagerParameter(): string
    {
        return $this->options['pager_param'];
    }

    /**
     * Is full search enabled
     */
    public function isSearchEnabled(): bool
    {
        return $this->options['search_enable'];
    }

    /**
     * Is search parsed
     */
    public function isSearchParsed(): bool
    {
        return $this->options['search_parse'];
    }

    /**
     * Is there a specifically configured search field
     */
    public function hasSearchField(): bool
    {
        return !empty($this->options['search_field']);
    }

    /**
     * Get search fields
     *
     * @return string[]
     */
    public function getSearchFields(): array
    {
        return (array)$this->options['search_field'];
    }

    /**
     * Get search parameter name
     */
    public function getSearchParameter(): string
    {
        return $this->options['search_param'] ?? '';
    }

    /**
     * Get sort field parameter
     */
    public function getSortFieldParameter(): string
    {
        return $this->options['sort_field_param'] ?? '';
    }

    /**
     * Get sort order parameter
     */
    public function getSortOrderParameter(): string
    {
        return $this->options['sort_order_param'] ?? '';
    }

    /**
     * Get default sort field
     */
    public function getDefaultSortField(): string
    {
        return $this->options['sort_default_field'] ?? '';
    }

    /**
     * Get default sort order
     */
    public function getDefaultSortOrder(): string
    {
        return $this->options['sort_default_order'] ?? '';
    }

    /**
     * Create query from array
     */
    public function createQueryFromArray(array $input): Query
    {
        return (new QueryFactory())->fromArray($this, $input);
    }

    /**
     * Create query from request
     */
    public function createQueryFromRequest(Request $request): Query
    {
        return (new QueryFactory())->fromRequest($this, $request);
    }
}
