<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\Query;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @todo unit test me seriously
 */
final class ViewBuilder
{
    private ViewRendererRegistry $viewRendererRegistry;

    private bool $locked = false;
    private $data = null;
    private ?Request $request = null;
    private string $rendererName = 'twig';
    private array $inputOptions = [];
    private array $viewOptions = [];
    private array $properties = [];
    private ?string $route = null;
    private array $routeParameters = [];

    public function __construct(ViewRendererRegistry $viewRendererRegistry)
    {
        $this->viewRendererRegistry = $viewRendererRegistry;
    }

    public function renderer(string $name): self
    {
        $this->dieIfLocked();

        $this->rendererName = $this->viewOptions['renderer'] = $name;

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

    public function pager(bool $enable = true, string $parameterName = 'page'): self
    {
        $this->dieIfLocked();

        $this->inputOptions['pager_enable'] = true;
        $this->inputOptions['pager_param'] = $parameterName;

        return $this;
    }

    public function addSort(string $name, ?string $label = null): self
    {
        $this->dieIfLocked();

        $this->inputOptions['sort_allowed_list'][$name] = $name ?? $label;

        return $this;
    }

    public function addFilter(Filter $filter): self
    {
        $this->dieIfLocked();

        $this->inputOptions['filter_list'][] = $filter;

        return $this;
    }

    public function defaultSort(string $propertyName, string $order = Query::SORT_DESC, string $propertyParameterName = 'st', string $orderParameterName = 'by'): self
    {
        $this->dieIfLocked();

        $this->inputOptions['sort_default_field'] = $propertyName;
        $this->inputOptions['sort_default_order'] = $order;
        $this->inputOptions['sort_field_param'] = $propertyParameterName;
        $this->inputOptions['sort_order_param'] = $orderParameterName;

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
     * @param array|callable|PropertyDescription|PropertyView $property
     *   If a callback, PropertyView will be set as virtual, sensible default,
     *   hence first callback parameter will be the object, second the property
     *   name.
     */
    public function property(string $name, $property = []): self
    {
        $this->dieIfLocked();

        if ($property instanceof PropertyView) {
            $this->properties[$name] = $property->rename($name);
        } else if ($property instanceof PropertyDescription) {
            $this->properties[$name] = $property->rename($name);
        } else if (\is_array($property)) {
            $this->properties[$name] = new PropertyView($name, null, $property);
        } else if (\is_callable($property)) {
            $this->properties[$name] = new PropertyView($name, null, [
                'callback' => $property,
                'virtual' => true,
            ]);
        } else {
            throw new \InvalidArgumentException(\sprintf("\$property must be an array or an instance of %s or %s", PropertyDescription::class, PropertyView::class));
        }

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

        $this->locked = true;

        return new ViewBuilderRenderer(
            $this->doBuild(),
            $this->viewRendererRegistry->getViewRenderer($this->rendererName)
        );
    }

    private function dieIfLocked(): void
    {
        if ($this->locked) {
            throw new \BadMethodCallException("You cannot modify an already consumed view builder.");
        }
    }

    private function doBuild(): View
    {
        $query = $this->doBuildQuery();

        if ($this->data instanceof DatasourceInterface) {
            $items = $this->data->getItems($query); 
        } else {
            $items = $this->data;
        }

        $view = new View($this->doBuildViewDefinition(), $items, $query);
        if ($this->route) {
            $view->setRoute($this->getRoute(), $this->getRouteParameters());
        } else if ($this->request) {
            $view->setRoute($this->request->attributes->get('_route'), $this->request->attributes->get('_route_params'));
        }

        return $view;
    }

    private function doBuildInputDefinition(): InputDefinition
    {
        $options = $this->inputOptions;

        // Eargerly add the default sort being an allowed sort, only in case
        // no sorts were specified. If sort were specified but the default is
        // not, keep the exceptions being raised.
        if (empty($options['sort_allowed_list']) && isset($options['sort_default_field'])) {
            $name = $options['sort_default_field'];
            $options['sort_allowed_list'][$name] = $name;
        }

        if ($this->data instanceof DatasourceInterface) {
            return InputDefinition::datasource($this->data, $options);
        }

        return new InputDefinition($options);
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
        $options = $this->viewOptions;
        $options['renderer'] = $this->rendererName;

        if ($this->properties) {
            foreach ($this->properties as $name => $property) {
                $options['properties'][$name] = $property;
            }
        }

        return new ViewDefinition($options);
    }
}

/**
 * @internal
 * @codeCoverageIgnore
 */
final class ViewBuilderRenderer
{
    private View $view;
    private ViewRenderer $renderer;

    public function __construct(View $view, ViewRenderer $renderer)
    {
        $this->view = $view;
        $this->renderer = $renderer;
    }

    public function renderInFile(string $filename): void
    {
        $this->renderer->renderInFile($this->view, $filename);
    }

    public function renderAsResponse(): Response
    {
        return $this->renderer->renderAsResponse($this->view);
    }

    public function renderInStream($resource): void
    {
        $this->renderer->renderInStream($this->view, $resource);
    }

    public function render(): string
    {
        return $this->renderer->render($this->view);
    }
}
