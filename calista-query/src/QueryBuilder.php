<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builder pattern implementation for end-users in order to allow fluent and
 * easy query definition.
 *
 * Using this object, you can define an input definition and plug a data source
 * without knowing the internals of calista.
 *
 * You can then use it, once built, to execute those queries and fetch the
 * resulting item collection.
 *
 * Additionally, you can also use it to fetch the intermediate internal objects
 * such as the Query object, for higher-level API to use.
 *
 * All get*() methods will lock the builder preventing further state updates.
 *
 * Calling getItems() in the end will return the set of computed items as an
 * iterable. If the underlaying data API uses generators, this will not consume
 * any memory, only initialise the generator and return it.
 *
 * Beware that getItems() should always be called only once, any attempt in
 * calling it more than once will result in an engine warning emitted, and
 * result will not be guaranted reproducible.
 *
 * @see \MakinaCorpus\Calista\View\ViewBuilder
 *   Which extends this object in order to render view on top of it.
 *
 * @todo
 *   unit test me seriously
 */
class QueryBuilder
{
    protected EventDispatcherInterface $eventDispatcher;
    protected bool $locked = false;
    protected /* null|iterable|callable */ $data = null;
    protected ?Request $request = null;
    protected array $inputOptions = [];

    protected ?Query $builtQuery = null;
    protected ?InputDefinition $builtInputDefinition = null;
    protected ?iterable $items = null;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * The default query values.
     *
     * @return $this
     */
    public function defaultQuery(array $values): static
    {
        $this->dieIfLocked();

        $this->inputOptions['default_query'] = $values;

        return $this;
    }

    /**
     * Set the base query filter.
     *
     * @return $this
     */
    public function baseQuery(array $values): static
    {
        $this->dieIfLocked();

        $this->inputOptions['base_query'] = $values;

        return $this;
    }

    /**
     * Can this selection allow user limit change.
     *
     * @return $this
     */
    public function allowLimitChange(int $max = Query::LIMIT_MAX, string $parameterName = 'limit'): static
    {
        $this->dieIfLocked();

        $this->inputOptions['limit_allowed'] = true;
        $this->inputOptions['limit_max'] = $max;
        $this->inputOptions['limit_param'] = $parameterName;

        return $this;
    }

    /**
     * Set default limit.
     *
     * @return $this
     */
    public function limit(int $limit): static
    {
        $this->dieIfLocked();

        $this->inputOptions['limit_default'] = $limit;

        return $this;
    }

    /**
     * Add a sort column.
     *
     * Sorts are applied using the same order of this method call.
     *
     * @return $this
     */
    public function sort(string $propertyName, ?string $label = null): static
    {
        $this->dieIfLocked();

        $this->inputOptions['sort_allowed_list'][$propertyName] = $label ?? $propertyName;

        return $this;
    }

    /**
     * Add multiple sort columns.
     *
     * This is equivalent as calling the sort() method many times.
     *
     * @return $this
     */
    public function sorts(array $sorts): static
    {
        foreach ($sorts as $propertyName => $label) {
            $this->sort($propertyName, $label);
        }

        return $this;
    }

    /**
     * Add an arbitrary filter.
     *
     * @return $this
     */
    public function filter(Filter $filter): static
    {
        $this->dieIfLocked();

        $this->inputOptions['filter_list'][] = $filter;

        return $this;
    }

    /**
     * Create and add a raw user input filter.
     *
     * @return $this
     */
    public function filterArbitrary(string $filterName, ?string $title): static
    {
        $this->filter(
            $this
                ->createFilter($filterName, $title)
                ->setArbitraryInput(true)
        );

        return $this;
    }

    /**
     * Create and add a choices map filter.
     *
     * @return $this
     */
    public function filterChoices(string $filterName, ?string $title, array $choices, ?string $noneOption = null): static
    {
        $this->filter(
            $this
                ->createFilter($filterName, $title)
                ->setChoicesMap($choices)
                ->setNoneOption($noneOption)
        );

        return $this;
    }

    /**
     * Create and add a date filter.
     *
     * @return $this
     */
    public function filterDate(string $filterName, ?string $title): static
    {
        $this->filter(
            $this
                ->createFilter($filterName, $title)
                ->setIsDate(true)
        );

        return $this;
    }

    /**
     * Add a collection of arbitrary filters.
     *
     * @return $this
     */
    public function filters(iterable $filters): static
    {
        $this->dieIfLocked();

        foreach ($filters as $filter) {
            $this->filter($filter);
        }

        return $this;
    }

    /**
     * Set default sort.
     *
     * @return $this
     */
    public function defaultSort(string $propertyName, string $propertyParameterName = 'st', string $orderParameterName = 'by', string $order = Query::SORT_ASC): static
    {
        $this->dieIfLocked();

        $this->inputOptions['sort_default_field'] = $propertyName;
        $this->inputOptions['sort_default_order'] = $order;
        $this->inputOptions['sort_field_param'] = $propertyParameterName;
        $this->inputOptions['sort_order_param'] = $orderParameterName;

        return $this;
    }

    /**
     * Set default property view options.
     *
     * This will affect only properties defined AFTER this method call.
     *
     * @return $this
     */
    public function defaultSortDesc(string $propertyName, string $propertyParameterName = 'st', string $orderParameterName = 'by'): static
    {
        $this->defaultSort($propertyName, $propertyParameterName, $orderParameterName, Query::SORT_DESC);

        return $this;
    }

    /**
     * Set incomming request.
     *
     * @return $this
     */
    public function request(Request $request): static
    {
        $this->dieIfLocked();

        // Normalizing is done later, once all data is set.
        $this->request = $request;

        return $this;
    }

    /**
     * Get incomming request if set.
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @param iterable|callable|DatasourceInterface $data
     *
     * @return $this
     */
    public function data($data): static
    {
        $this->dieIfLocked();

        // Normalizing is done later, once all data is set.
        $this->data = $data;

        return $this;
    }

    /**
     * Build and return query.
     *
     * This method will lock the builder.
     */
    public function getQuery(): Query
    {
        if (!$this->locked) {
            $this->locked = true;
        }
        if ($this->builtQuery) {
            return $this->builtQuery;
        }

        $inputDefinition = $this->getInputDefinition();

        if ($this->request) {
            return $this->builtQuery = Query::fromRequest($inputDefinition, $this->request);
        }

        return $this->builtQuery = Query::fromArray($inputDefinition);
    }

    /**
     * Get input definition after build.
     *
     * This method will lock the builder.
     */
    public function getInputDefinition(): InputDefinition
    {
        if (!$this->locked) {
            $this->locked = true;
        }
        if ($this->builtInputDefinition) {
            return $this->builtInputDefinition;
        }

        $options = $this->inputOptions;

        // Eargerly add the default sort being an allowed sort, only in case
        // no sorts were specified. If sort were specified but the default is
        // not, keep the exceptions being raised.
        if (empty($options['sort_allowed_list']) && isset($options['sort_default_field'])) {
            $propertyName = $options['sort_default_field'];
            $options['sort_allowed_list'][$propertyName] = $propertyName;
        }

        if ($this->data instanceof DatasourceInterface) {
            return $this->builtInputDefinition = InputDefinition::datasource($this->data, $options);
        }

        return $this->builtInputDefinition = new InputDefinition($options);
    }

    /**
     * Get items.
     *
     * This will return an iterable, any iterable, it can be an array, an
     * \Iterator, an \IteratorAggregate, a \Generator etc...
     *
     * Implementation will depend upon what was passed to the data() method.
     *
     * If you call this method more than once, it's not guaranted to return
     * the same result, since it will depend upon the data() method input.
     */
    public function getItems(): iterable
    {
        if (null === $this->data) {
            throw new \BadMethodCallException("Data was not set, you cannot fetch items.");
        }

        if ($this->data instanceof DatasourceInterface) {
            return $this->data->getItems($this->getQuery());
        }

        if (\is_callable($this->data)) {
            return ($this->data)($this->getQuery());
        }

        return $this->data;
    }

    /**
     * Create a default filter.
     */
    protected function createFilter(string $filterName, ?string $title = null, ?string $description = null): DefaultFilter
    {
        return new DefaultFilter($filterName, $title, $description);
    }

    /**
     * Raise an exception if current builder is locked.
     */
    protected function dieIfLocked(): void
    {
        if ($this->locked) {
            throw new \BadMethodCallException("You cannot modify an already consumed view builder.");
        }
    }
}
