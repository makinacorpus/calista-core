<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Parses and cleanups the incomming query from a Symfony request.
 */
final class QueryFactory
{
    /**
     * Create query from array.
     */
    public function fromArray(InputDefinition $inputDefinition, array $input, string $route = '', array $protected = []): Query
    {
        // Append filter values from the request into the query
        $input = $this->normalizeInput($input);

        // We'll start with route parameters being identical that the global
        // query, we will prune default values later to make it shorter
        $routeParameters = $input;

        $baseQuery = $inputDefinition->getBaseQuery();

        return new Query(
            $inputDefinition,
            $route,
            $this->flattenQuery($this->applyBaseQuery($input, $baseQuery)),
            $protected + $this->flattenQuery($this->applyBaseQuery($routeParameters, $baseQuery, true), true),
            $protected
        );
    }

    /**
     * Create a query from array.
     */
    public function fromArbitraryArray(array $input): Query
    {
        return $this->fromArray(new InputDefinition([
            'base_query'          => [],
            'limit_allowed'       => true,
            'limit_default'       => Query::LIMIT_DEFAULT,
            'limit_param'         => 'limit',
            'pager_enable'        => true,
            'pager_param'         => 'page',
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

        // We must keep track of Symfony own route parameters: when base
        // query is processed, parameters included within the base query
        // are removed, in order for the user to NOT see them in the URL.
        // Nevertheless, if they are mandatory to generate the current
        // route, we must restore them in routeParameters so that the
        // calls to the twig {{ path(query.route, query.routeParams) }}
        // will give them to the router.
        $protected = [];
        $routeParameters = $request->query->all();

        // Symfony just replicates all query parameters and route parameters
        // raw values into the _route_params array, which allows to use it
        // transparently to regenerate the exact same URL.
        if ($request->attributes->has('_route_params')) {
            $routeParameters += $protected = $request->attributes->get('_route_params', []);
        }

        // Workaround for Drupal 8 context, we sadly had no other choice than
        // working with it from here, Drupal 8 has the bad habbit of misusing
        // APIs under it. It puts a ton of meta-information including objects
        // into the _route_params array, which makes everything explode when
        // trying to regenerate the URL using those parameters.
        if (isset($routeParameters['_raw_variables'])) {
            $routeParameters = $routeParameters['_raw_variables'];
            // Des fois oui, des fois non, avec Drupal on ne sait jamais vraiment.
            if ($routeParameters instanceof ParameterBag) {
                $routeParameters = $routeParameters->all();
            }
            $routeParameters += $request->query->all();
        }

        return $this->fromArray($inputDefinition, $routeParameters, $route, $protected);
    }

    /**
     * In the given query, flatten the given parameter.
     *
     * All values in the query that are arrays with a single value will be
     * flattened to be a value instead of an array, this way we limit the
     * potential wrong type conversions with special parameters such as the
     * page number.
     */
    private function flattenQuery(array $query, bool $isRouteParameters = false): array
    {
        foreach ($query as $key => $values) {
            if (\is_array($values)) {
                if (1 === \count($values)) {
                    $query[$key] = \reset($values);
                } else if ($isRouteParameters) {
                    $query[$key] = Query::valuesEncode($values);
                }
            }
        }

        return $query;
    }

    /**
     * Merge all queries altogether.
     *
     * @param array[][] $queries
     *   Array of queries to merge.
     *
     * @return string[][]
     *   Merged queries.
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
     * not in base query boundaries.
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
