<?php

namespace MakinaCorpus\Calista\Query;

use Symfony\Component\HttpFoundation\Request;

/**
 * Parses and cleanups the incomming query from a Symfony request
 */
class QueryFactory
{
    /**
     * Create query from array
     */
    public function fromArray(InputDefinition $inputDefinition, array $input, string $route = ''): Query
    {
        $rawSearchString = '';
        $searchParameter = $inputDefinition->getSearchParameter();

        // Append filter values from the request into the query
        $input = $this->normalizeInput($input);

        // We'll start with route parameters being identical that the global
        // query, we will prune default values later to make it shorter
        $routeParameters = $input;

        // Deal with search
        if ($inputDefinition->isSearchEnabled() && $searchParameter && !empty($input[$searchParameter])) {
            $rawSearchString = $input[$searchParameter];

            // Parse search and merge it properly to the incomming query
            if ($inputDefinition->isSearchParsed()) {
                $parsedSearch = (new QueryStringParser())->parse($rawSearchString, $searchParameter);

                if ($parsedSearch) {
                    // Filters should not contain the search parameter, since
                    // it has been parsed and normalize, we remove it then merge
                    // the parsed one
                    unset($input[$searchParameter]);
                    $input = $this->mergeQueries([$parsedSearch, $input]);
                }
            }
        }

        // Route parameters must contain the raw search string and not the
        // parsed search string to be able to rebuild correctly links
        if ($rawSearchString) {
            $routeParameters[$searchParameter] = $rawSearchString;
        }

        $baseQuery = $inputDefinition->getBaseQuery();

        return new Query(
            $inputDefinition,
            $route,
            $this->flattenQuery($this->applyBaseQuery($input, $baseQuery), [$searchParameter]),
            $this->flattenQuery($this->applyBaseQuery($routeParameters, $baseQuery, true), [$searchParameter], true),
            $baseQuery
        );
    }

    /**
     * Create a query from array
     */
    public function fromArbitraryArray(array $input): Query
    {
        return $this->fromArray(new InputDefinition([
            'base_query'          => [],
            'display_param'       => 'display',
            'limit_allowed'       => true,
            'limit_default'       => Query::LIMIT_DEFAULT,
            'limit_param'         => 'limit',
            'pager_enable'        => true,
            'pager_param'         => 'page',
            'search_enable'       => true,
            'search_param'        => 'search',
            'search_parse'        => true,
            'sort_default_field'  => '',
            'sort_default_order'  => Query::SORT_DESC,
            'sort_field_param'    => 'st',
            'sort_order_param'    => 'by',
        ]), $input);
    }

    /**
     * Create query from request
     */
    public function fromRequest(InputDefinition $inputDefinition, Request $request): Query
    {
        $route = $request->attributes->get('_route', '');
        $input = array_merge($request->query->all(), $request->attributes->get('_route_params', []));

        return $this->fromArray($inputDefinition, $input, $route);
    }

    /**
     * In the given query, flatten the given parameter.
     *
     * All values in the query that are arrays with a single value will be
     * flattened to be a value instead of an array, this way we limit the
     * potential wrong type conversions with special parameters such as the
     * page number.
     *
     * All parameters in the $needsImplode array will be imploded using a
     * whitespace, this is useful for the full text search parameter, that
     * needs to remain a single string.
     */
    private function flattenQuery(array $query, array $needsImplode = [], bool $isRouteParameters = false): array
    {
        foreach ($query as $key => $values) {
            if (is_array($values)) {
                if (1 === count($values)) {
                    $query[$key] = reset($values);
                } else if (in_array($key, $needsImplode)) {
                    $query[$key] = implode(' ', $values);
                } else if ($isRouteParameters) {
                    $query[$key] = implode(Query::URL_VALUE_SEP, $values);
                }
            }
        }

        return $query;
    }

    /**
     * Merge all queries altogether
     *
     * @param array[][] $queries
     *   Array of queries to merge
     *
     * @return string[][]
     *   Merged queries
     */
    private function mergeQueries(array $queries): array
    {
        $ret = [];

        foreach ($queries as $query) {
            foreach ($query as $field => $values) {

                // Normalize all values to arrays
                if (!is_array($values)) {
                    $values = [$values];
                }

                // If value already exists in ret, merge it with the new values
                // and drop the duplicated values altogether
                if (isset($ret[$field])) {
                    $ret[$field] = array_unique(array_merge($ret[$field], $values));
                } else {
                    $ret[$field] = array_unique($values);
                }
            }
        }

        return $ret;
    }

    /**
     * From the incoming query, prepare the $query array
     *
     * @param string[]|string[][]
     *   Input
     *
     * @return string[]|string[][]
     *   Prepare query parameters, using base query and filters
     */
    private function normalizeInput(array $query, array $exclude = ['q']): array
    {
        // Proceed to unwanted parameters exclusion
        if ($exclude) {
            foreach ($exclude as $parameter) {
                unset($query[$parameter]);
            }
        }

        // Normalize input
        foreach ($query as $key => $value) {
            // Drops all empty values (but not 0 or false)
            if ('' === $value || null === $value || [] === $value) {
                unset($query[$key]);
                continue;
            }
            // Normalize non-array input using the value separator
            if (is_string($value) && false !== strpos($value, Query::URL_VALUE_SEP)) {
                $query[$key] = $this->normalizeInput(explode(Query::URL_VALUE_SEP, $value), []);
            }
        }

        return $query;
    }

    /**
     * From the given prepared but unfiltered query, drop all values that are
     * not in base query boundaries
     */
    private function applyBaseQuery(array $query, array $baseQuery, bool $isRouteParameters = false): array
    {
        // Ensure that query values are in base query bounds
        foreach ($baseQuery as $name => $allowed) {
            if (isset($query[$name])) {
                $input = $query[$name];

                // Normalize
                if (!is_array($allowed)) {
                    $allowed = [$allowed];
                }
                if (!is_array($input)) {
                    $input = [$input];
                }

                // Restrict to fixed bounds
                $filterValues = array_unique(array_intersect($input, $allowed));

                if ($isRouteParameters) {
                    // When filter is equal to base filter, it must be excluded
                    // from the route parameters, they are hardcoded by the
                    // controller and not derived from the query
                    if (count($filterValues) !== count($input)) {
                        $query[$name] = $filterValues;
                    } else if (count($filterValues) !== count($allowed)) {
                        $query[$name] = $filterValues;
                    } else {
                        sort($filterValues);
                        sort($allowed);
                        if ($filterValues !== $allowed) {
                            $query[$name] = $filterValues;
                        } else {
                            unset($query[$name]);
                        }
                    }
                } else {
                    $query[$name] = $filterValues;
                }
            } else if (!$isRouteParameters) {
                $query[$name] = $allowed;
            }
        }

        return $query;
    }
}
