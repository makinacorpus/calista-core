<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

use Symfony\Component\HttpFoundation\Request;

/**
 * Sanitized version of an incomming query.
 *
 * @todo support maximum limit
 */
class Query
{
    const LIMIT_DEFAULT = 10;
    const LIMIT_MAX = 1000;
    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';
    const URL_VALUE_SEP = '|';

    private array $filters = [];
    private array $others = [];
    private InputDefinition $inputDefinition;
    private int $limit = self::LIMIT_DEFAULT;
    private int $page = 1;
    private ?string $sortField = null;
    private $sortOrder = self::SORT_DESC;

    /**
     * Default constructor.
     *
     * @param InputDefinition $inputDefinition
     *   Current configuration.
     * @param string[] $filters
     *   Current filters (including defaults).
     */
    public function __construct(InputDefinition $inputDefinition, array $filters = [], array $others = [])
    {
        $this->inputDefinition = $inputDefinition;
        $this->filters = $filters;
        $this->others = $others;

        $this->findRange();
        $this->findSort();

        // Now for security, prevent anything that is not a filter from
        // existing into the filter array
        foreach (\array_keys($this->filters) as $name) {
            if (!$inputDefinition->isFilterAllowed($name)) {
                unset($this->filters[$name]);
            }
        }
    }

    /**
     * Create empty instance
     */
    public static function empty(): self
    {
        return new self(new InputDefinition(), []);
    }

    /**
     * Create query from array.
     */
    public static function fromArray(InputDefinition $inputDefinition, array $input = []): Query
    {
        $otherKeys = [
            $inputDefinition->getLimitParameter() => true,
            $inputDefinition->getPagerParameter() => true,
            $inputDefinition->getSortFieldParameter() => true,
            $inputDefinition->getSortOrderParameter() => true,
        ];

        $baseQuery = $inputDefinition->getBaseQuery();

        $filters = $baseQuery;
        foreach (\array_diff_key($input, $otherKeys) as $name => $value) {
            $value = self::secureValue($name, $value, $baseQuery);
            if (null !== $value) {
                $filters[$name] = $value;
            }
        }

        // If user input is empty, apply default query instead.
        if (empty($input)) {
            foreach ($inputDefinition->getDefaultQuery() as $name => $value) {
                $filters[$name] = self::secureValue($name, $value, $baseQuery);
            }
        }

        $others = \array_intersect_key($input, $otherKeys);

        return new Query($inputDefinition, $filters, $others);
    }

    /**
     * Create query from request.
     */
    public static function fromRequest(InputDefinition $inputDefinition, Request $request): Query
    {
        if ($request->isMethod('post')) {
            return self::fromArray($inputDefinition, $request->query->all() + $request->request->all());
        }
        return self::fromArray($inputDefinition, $request->query->all());
    }

    /**
     * Create a query from array.
     */
    public static function fromArbitraryArray(array $input): Query
    {
        return self::fromArray(new InputDefinition([
            'base_query' => [],
            'limit_allowed' => true,
            'limit_default' => Query::LIMIT_DEFAULT,
            'limit_param' => 'limit',
            'pager_enable' => true,
            'pager_param' => 'page',
            'sort_default_field' => '',
            'sort_default_order' => Query::SORT_DESC,
            'sort_field_param' => 'st',
            'sort_order_param' => 'by',
        ]), $input);
    }

    /**
     * Decode values from a single query parameter.
     */
    public static function valuesDecode($values): array
    {
        if (!\is_array($values)) {
            if (\is_iterable($values)) {
                $values = \iterator_to_array($values);
            } else if (\is_string($values)) {
                // \trim() here because in some bugguy client cases, the
                // separator can appear at edges, without any value aside.
                $values = \explode(self::URL_VALUE_SEP, \trim($values, self::URL_VALUE_SEP));
            } else {
                $values = [$values];
            }
        }
        return \array_map('trim', $values);
    }

    /**
     * Encode values to be used as a single query paramter.
     */
    public static function valuesEncode($values): string
    {
        if (\is_array($values)) {
            \sort($values);
            return \implode(self::URL_VALUE_SEP, $values);
        }
        if (\is_iterable($values)) {
            $values = \iterator_to_array($values);
            \sort($values);
            return \implode(self::URL_VALUE_SEP, $values);
        }
        return (string)$values;
    }

    /**
     * Normalize a single value to be an array of values.
     */
    private static function expandValue($value): ?array
    {
        // Drops all empty values (but not 0 or false).
        if ('' === $value || null === $value || [] === $value) {
            return null;
        }
        // Normalize non-array input using the value separator.
        if (\is_string($value)) {
            return Query::valuesDecode($value);
        }
        if (!\is_array($value)) {
            // @todo This might explode.
            return [(string)$value];
        }
        return $value;
    }

    /**
     * Normalize then restrict filter values to base query.
     */
    private static function secureValue(string $name, $value, array $baseQuery): ?array
    {
        $value = self::expandValue($value);
        $allowed = self::expandValue($baseQuery[$name] ?? null);

        if (null === $value || [] === $value) {
            return $value;
        }

        if (null !== $allowed) {
            // Restrict possible values to base query bounds.
            $value = \array_unique(\array_intersect($value, $allowed));

            // If restriction gave nothing, force filter to be restored to base
            // query default instead.
            if (empty($value)) {
                return $allowed;
            }
        }

        return $value;
    }

    /**
     * Find range from query.
     */
    private function findRange(): void
    {
        $this->limit = $this->inputDefinition->getDefaultLimit();

        if ($this->inputDefinition->isLimitAllowed()) {
            // Limit can be changed, we must find it from the parameters
            $limitParameter = $this->inputDefinition->getLimitParameter();
            if ($limitParameter && isset($this->others[$limitParameter])) {
                $this->limit = (int)$this->others[$limitParameter];
                // Additional security, do not allow negative or 0 limit
                if ($this->limit <= 0) {
                    $this->limit = $this->inputDefinition->getDefaultLimit();
                }
            }
        }

        // Pager initialization, only if enabled
        if ($this->inputDefinition->isPagerEnabled()) {
            $pageParameter = $this->inputDefinition->getPagerParameter();
            if ($pageParameter && isset($this->others[$pageParameter])) {
                $this->page = (int)$this->others[$pageParameter];
            }

            // Additional security, do not allow negative or 0 page
            if ($this->page <= 0) {
                $this->page = 1;
            }
        }
    }

    /**
     * Find sort from query.
     */
    private function findSort(): void
    {
        $this->sortField = $this->inputDefinition->getDefaultSortField();
        $this->sortOrder = $this->inputDefinition->getDefaultSortOrder();

        $sortFieldParameter = $this->inputDefinition->getSortFieldParameter();
        if ($sortFieldParameter && isset($this->others[$sortFieldParameter])) {
            $sortField = $this->others[$sortFieldParameter];
            if ($this->inputDefinition->isSortAllowed($sortField)) {
                $this->sortField = (string)$this->others[$sortFieldParameter];
            }
        }

        $sortOrderParameter = $this->inputDefinition->getSortOrderParameter();
        if ($sortOrderParameter && isset($this->others[$sortOrderParameter])) {
            $this->sortOrder = \strtolower($this->others[$sortOrderParameter]) === self::SORT_DESC ? self::SORT_DESC : self::SORT_ASC;
        }
    }

    /**
     * Get value from a filter, it might be an expanded array of values.
     *
     * @return string|string[]
     */
    public function get(string $name, $default = '')
    {
        if (!\array_key_exists($name, $this->filters)) {
            return $default;
        }

        $values = $this->filters[$name];

        if ($values && \is_array($values) && 1 === \count($values)) {
            return \reset($values);
        }

        return $values;
    }

    /**
     * Does the filter is set.
     */
    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->filters);
    }

    /**
     * Is this query empty: being empty means there is no filter, but limit
     * and order information can be set.
     */
    public function isEmpty(): bool
    {
        return empty($this->filters);
    }

    /**
     * Get input definition.
     */
    public function getInputDefinition(): InputDefinition
    {
        return $this->inputDefinition;
    }

    /**
     * Is a sort field set.
     */
    public function hasSortField(): bool
    {
        return !!$this->sortField;
    }

    /**
     * Get sort field.
     */
    public function getSortField(): ?string
    {
        return $this->sortField;
    }

    /**
     * Get sort order.
     */
    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    /**
     * Is sort order ascending.
     */
    public function isSortAsc(): bool
    {
        return $this->sortOrder !== self::SORT_DESC;
    }

    /**
     * Get limit.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Get offset.
     */
    public function getOffset(): int
    {
        return $this->limit * \max([0, $this->page - 1]);
    }

    /**
     * Get page number, starts with 1.
     */
    public function getCurrentPage(): int
    {
        return $this->page;
    }

    /**
     * Get the complete filter array.
     */
    public function all(): array
    {
        return $this->filters;
    }

    public function toArray(): array
    {
        return $this->filters + $this->others;
    }
}
