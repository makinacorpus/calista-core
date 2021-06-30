<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\Query\DefaultFilter;
use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\Event\ViewBuilderEvent;
use MakinaCorpus\Calista\View\ViewBuilder\ViewBuilderRenderer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @todo unit test me seriously
 */
final class ViewBuilder
{
    private ViewRendererRegistry $viewRendererRegistry;
    private EventDispatcherInterface $eventDispatcher;

    private bool $locked = false;
    private $data = null;
    private ?Request $request = null;
    private string $rendererName = 'twig';
    private array $inputOptions = [];
    private array $viewOptions = [];
    private array $defaultPropertyView = [];
    private array $properties = [];
    private array $propertyLabels = [];
    private ?string $route = null;
    private array $routeParameters = [];

    private ?InputDefinition $builtInputDefinition = null;
    private ?ViewDefinition $builtViewDefinition = null;
    private ?View $builtView = null;

    public function __construct(
        ViewRendererRegistry $viewRendererRegistry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->viewRendererRegistry = $viewRendererRegistry;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function renderer(string $name, array $extraOptions = []): self
    {
        $this->dieIfLocked();

        $this->rendererName = $this->viewOptions['renderer'] = $name;
        $this->viewOptions['extra'] = $extraOptions + ($this->viewOptions['extra'] ?? []);

        return $this;
    }

    /**
     * Add extra option value for view renderer.
     */
    public function extra(string $name, $value): self
    {
        $this->dieIfLocked();

        $this->viewOptions['extra'][$name] = $value;

        return $this;
    }

    public function defaultQuery(array $values): self
    {
        $this->dieIfLocked();

        $this->inputOptions['default_query'] = $values;

        return $this;
    }

    public function baseQuery(array $values): self
    {
        $this->dieIfLocked();

        $this->inputOptions['base_query'] = $values;

        return $this;
    }

    public function allowLimitChange(int $max = Query::LIMIT_MAX, string $parameterName = 'limit'): self
    {
        $this->dieIfLocked();

        $this->inputOptions['limit_allowed'] = true;
        $this->inputOptions['limit_max'] = $max;
        $this->inputOptions['limit_param'] = $parameterName;

        return $this;
    }

    public function enablePager(bool $enable = true, string $parameterName = 'page'): self
    {
        $this->dieIfLocked();

        $this->inputOptions['pager_enable'] = true;
        $this->inputOptions['pager_param'] = $parameterName;

        return $this;
    }

    public function enableFilters(string ... $names): self
    {
        $this->dieIfLocked();

        foreach ($names as $name) {
            // @todo Update ViewDefinition to handle a graylist were filters
            //   can be explicitely set to false, where defaults don't need
            //   to be added to the list.
            if (!\in_array($name, $this->viewOptions['enabled_filters'])) {
                $this->viewOptions['enabled_filters'][] = $name;
            }
        }

        return $this;
    }

    public function disableFilter(string $name): self
    {
        $this->dieIfLocked();

        // This one is tricky, if no enabled filters were specified, in order
        // to disable a single one, we need to enfore all filters to be present
        // then remove the one that has been asked for removal.
        // This will cause us trouble, because filters if not explicitely set
        // prior to this method call prevent us to do that. We need to delay
        // disabling of filters in the build method.
        throw new \Exception("Implement me properly.");

        return $this;
    }

    public function showPager(bool $enable = true): self
    {
        $this->dieIfLocked();

        $this->viewOptions['show_pager'] = $enable;

        return $this;
    }

    public function showFilters(bool $enable = true): self
    {
        $this->dieIfLocked();

        $this->viewOptions['show_filters'] = $enable;

        return $this;
    }

    public function showSort(bool $enable = true): self
    {
        $this->dieIfLocked();

        $this->viewOptions['show_sort'] = $enable;

        return $this;
    }

    /**
     * Set default limit.
     */
    public function limit(int $limit): self
    {
        $this->dieIfLocked();

        $this->inputOptions['limit_default'] = $limit;

        return $this;
    }

    public function sort(string $name, ?string $label = null): self
    {
        $this->dieIfLocked();

        $this->inputOptions['sort_allowed_list'][$name] = $name ?? $label;

        return $this;
    }

    public function sorts(array $sorts): self
    {
        foreach ($sorts as $name => $label) {
            $this->sort($name, $label);
        }

        return $this;
    }

    public function filter(Filter $filter): self
    {
        $this->dieIfLocked();

        $this->inputOptions['filter_list'][] = $filter;

        return $this;
    }

    public function filterArbitrary(string $name, ?string $title): self
    {
        $this->filter(
            $this
                ->createFilter($name, $title)
                ->setArbitraryInput(true)
        );

        return $this;
    }

    public function filterChoices(string $name, ?string $title, array $choices, ?string $noneOption = null): self
    {
        $this->filter(
            $this
                ->createFilter($name, $title)
                ->setChoicesMap($choices)
                ->setNoneOption($noneOption)
        );

        return $this;
    }

    public function filterDate(string $name, ?string $title): self
    {
        $this->filter(
            $this
                ->createFilter($name, $title)
                ->setIsDate(true)
        );

        return $this;
    }

    public function filters(iterable $filters): self
    {
        $this->dieIfLocked();

        foreach ($filters as $filter) {
            $this->filter($filter);
        }

        return $this;
    }

    public function defaultPropertyView(array $options): self
    {
        $this->dieIfLocked();

        $this->defaultPropertyView = $options;

        return $this;
    }

    public function defaultSort(string $propertyName, string $propertyParameterName = 'st', string $orderParameterName = 'by', string $order = Query::SORT_ASC): self
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
     */
    public function defaultSortDesc(string $propertyName, string $propertyParameterName = 'st', string $orderParameterName = 'by'): self
    {
        $this->defaultSort($propertyName, $propertyParameterName, $orderParameterName, Query::SORT_DESC);

        return $this;
    }

    /**
     * @param Request $request
     */
    public function request(Request $request): self
    {
        $this->dieIfLocked();

        // Normalizing is done later, once all data is set.
        $this->request = $request;

        return $this;
    }

    /**
     * @param iterable|callable|DatasourceInterface $data
     */
    public function data($data): self
    {
        $this->dieIfLocked();

        // Normalizing is done later, once all data is set.
        $this->data = $data;

        return $this;
    }

    /**
     * @param array|callable $callback
     *   Either an arbitrary array of values that will override all items
     *   values, or a callable whose first argument will be item, that will
     *   be execute for each row, allowing you to precompute a set of values.
     *   Callback return must be an array whose names are properties names.
     */
    public function preload($callback): self
    {
        $this->dieIfLocked();

        if (!\is_array($callback) && !\is_callable($callback)) {
            throw new \InvalidArgumentException("Preload \$callback must be an array or a callable.");
        }

        $this->viewOptions['preload'] = $callback;

        return $this;
    }

    /**
     * @param array|callable|PropertyDescription|PropertyView $property
     *   If a callback, PropertyView will be set as virtual, sensible default,
     *   hence first callback parameter will be the object, second the property
     *   name.
     */
    public function property(string $name, $property = [], ?string $label = null, bool $hidden = false): self
    {
        $this->dieIfLocked();

        if ($property instanceof PropertyView) {
            $this->properties[$name] = $property->rename($name, $label, ['hidden' => $hidden]);
        } else if ($property instanceof PropertyDescription) {
            $this->properties[$name] = $property->rename($name, $label, ['hidden' => $hidden]);
        } else if (\is_array($property)) {
            $this->properties[$name] = new PropertyView($name, null, ['hidden' => $hidden] + $property + ['label' => $label] + $this->defaultPropertyView);
        } else if (\is_callable($property)) {
            $this->properties[$name] = new PropertyView($name, null, [
                'callback' => $property,
                'hidden' => $hidden,
                'label' => $label,
                'virtual' => true,
            ] + $this->defaultPropertyView);
        } else {
            throw new \InvalidArgumentException(\sprintf("\$property must be an array or an instance of %s or %s", PropertyDescription::class, PropertyView::class));
        }

        return $this;
    }

    /**
     * Alias of property() which will set the 'hidden' option to true.
     *
     * As of now, this only has an effect on REST API.
     *
     * @see self::property()
     */
    public function hiddenProperty(string $name, $property = [], ?string $label = null): self
    {
        $this->property($name, $property, $label, true);

        return $this;
    }

    /**
     * Change property label.
     */
    public function propertyLabel(string $name, string $label): self
    {
        $this->dieIfLocked();

        $this->propertyLabels[$name] = $label;

        return $this;
    }

    public function viewOptions(array $options): self
    {
        $this->dieIfLocked();

        // @todo proper recursive merge.
        $this->viewOptions += $options;

        return $this;
    }

    public function template(string $name): self
    {
        $this->dieIfLocked();

        $this->viewOptions['extra']['template'] = $name;

        return $this;
    }

    public function route(string $route, array $parameters = []): self
    {
        $this->dieIfLocked();

        $this->route = $route;
        $this->routeParameters = $parameters;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function build(): ViewBuilderRenderer
    {
        $this->dieIfLocked();

        $this->eventDispatcher->dispatch(new ViewBuilderEvent($this), ViewBuilderEvent::EVENT_BUILD);

        $this->locked = true;

        return new ViewBuilderRenderer(
            $this->doBuild(),
            $this->viewRendererRegistry->getViewRenderer($this->rendererName)
        );
    }

    /**
     * Get input definition after build.
     */
    public function getInputDefinition(): InputDefinition
    {
        if (!$this->locked) {
            $this->locked = true;
        }

        return $this->doBuildInputDefinition();
    }

    /**
     * Get input definition after build.
     */
    public function getViewDefinition(): ViewDefinition
    {
        if (!$this->locked) {
            $this->locked = true;
        }

        return $this->doBuildViewDefinition();
    }

    /**
     * Get view.
     */
    public function getView(): View
    {
        if (!$this->locked) {
            $this->locked = true;
        }

        return $this->doBuild();
    }

    private function createFilter(string $name, ?string $title = null, ?string $description = null): Filter
    {
        return new DefaultFilter($name, $title, $description);
    }

    private function dieIfLocked(): void
    {
        if ($this->locked) {
            throw new \BadMethodCallException("You cannot modify an already consumed view builder.");
        }
    }

    private function dieIfNotLocked(): void
    {
        if ($this->locked) {
            throw new \BadMethodCallException("You cannot fetch data before calling build().");
        }
    }

    private function doBuildItems(): array
    {
        $query = $this->doBuildQuery();

        if ($this->data instanceof DatasourceInterface) {
            $items = $this->data->getItems($query);
        } else if (\is_callable($this->data)) {
            $items = ($this->data)($query);
        } else {
            $items = $this->data;
        }

        return [$query, $items];
    }

    private function doBuild(): View
    {
        if ($this->builtView) {
            return $this->builtView;
        }

        list($query, $items) = $this->doBuildItems();

        $view = new View($this->doBuildViewDefinition(), $items, $query);
        if ($this->route) {
            $view->setRoute($this->getRoute(), $this->getRouteParameters());
        } else if ($this->request) {
            $view->setRoute($this->request->attributes->get('_route'), $this->request->attributes->get('_route_params'));
        }

        return $this->builtView = $view;
    }

    private function doBuildInputDefinition(): InputDefinition
    {
        if ($this->builtInputDefinition) {
            return $this->builtInputDefinition;
        }

        $options = $this->inputOptions;

        // Eargerly add the default sort being an allowed sort, only in case
        // no sorts were specified. If sort were specified but the default is
        // not, keep the exceptions being raised.
        if (empty($options['sort_allowed_list']) && isset($options['sort_default_field'])) {
            $name = $options['sort_default_field'];
            $options['sort_allowed_list'][$name] = $name;
        }

        if ($this->data instanceof DatasourceInterface) {
            return $this->builtInputDefinition = InputDefinition::datasource($this->data, $options);
        }

        return $this->builtInputDefinition = new InputDefinition($options);
    }

    private function doBuildQuery(): Query
    {
        $inputDefinition = $this->doBuildInputDefinition();

        if ($this->request) {
            return Query::fromRequest($inputDefinition, $this->request);
        }

        return Query::fromArray($inputDefinition);
    }

    private function doBuildViewDefinition(): ViewDefinition
    {
        if ($this->builtViewDefinition) {
            return $this->builtViewDefinition;
        }

        $options = $this->viewOptions;
        $options['renderer'] = $this->rendererName;

        if ($this->properties) {
            foreach ($this->properties as $name => $property) {
                \assert($property instanceof PropertyView || $property instanceof PropertyDescription);

                $newLabel = $this->propertyLabels[$name] ?? null;
                if ($newLabel) {
                    $options['properties'][$name] = $property->rename($name, $newLabel);
                } else {
                    $options['properties'][$name] = $property;
                }
            }
        }

        return $this->builtViewDefinition = new ViewDefinition($options);
    }
}
