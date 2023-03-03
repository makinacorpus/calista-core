<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\Query\QueryBuilder;
use MakinaCorpus\Calista\View\Event\ViewBuilderEvent;
use MakinaCorpus\Calista\View\ViewBuilder\ViewBuilderRenderer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @todo unit test me seriously
 */
final class ViewBuilder extends QueryBuilder
{
    private ViewRendererRegistry $viewRendererRegistry;

    private string $rendererName = 'twig';
    private array $viewOptions = [];
    private array $defaultPropertyView = [];
    private array $properties = [];
    private array $propertyLabels = [];
    private ?string $route = null;
    private array $routeParameters = [];

    private ?ViewDefinition $builtViewDefinition = null;
    private ?View $builtView = null;

    public function __construct(
        ViewRendererRegistry $viewRendererRegistry,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($eventDispatcher);

        $this->viewRendererRegistry = $viewRendererRegistry;
    }

    /**
     * @return $this
     */
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

    /**
     * @return $this
     */
    public function enablePager(bool $enable = true, string $parameterName = 'page'): self
    {
        $this->dieIfLocked();

        $this->inputOptions['pager_enable'] = true;
        $this->inputOptions['pager_param'] = $parameterName;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableFilters(string ... $names): self
    {
        $this->dieIfLocked();

        foreach ($names as $name) {
            // @todo Update ViewDefinition to handle a graylist were filters
            //   can be explicitely set to false, where defaults don't need
            //   to be added to the list.
            if (!\in_array($name, $this->viewOptions['enabled_filters'] ?? [])) {
                $this->viewOptions['enabled_filters'][] = $name;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
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

    /**
     * @return $this
     */
    public function showPager(bool $enable = true): self
    {
        $this->dieIfLocked();

        $this->viewOptions['show_pager'] = $enable;

        return $this;
    }

    /**
     * @return $this
     */
    public function showFilters(bool $enable = true): self
    {
        $this->dieIfLocked();

        $this->viewOptions['show_filters'] = $enable;

        return $this;
    }

    /**
     * @return $this
     */
    public function showSort(bool $enable = true): self
    {
        $this->dieIfLocked();

        $this->viewOptions['show_sort'] = $enable;

        return $this;
    }

    /**
     * @return $this
     */
    public function defaultPropertyView(array $options): self
    {
        $this->dieIfLocked();

        $this->defaultPropertyView = $options;

        return $this;
    }

    /**
     * @param array|callable $callback
     *   Either an arbitrary array of values that will override all items
     *   values, or a callable whose first argument will be item, that will
     *   be execute for each row, allowing you to precompute a set of values.
     *   Callback return must be an array whose names are properties names.
     *
     * @return $this
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
     * Add property.
     *
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
     * Add property to be raw-displayed (without any filtering).
     *
     * @param array|callable|PropertyDescription|PropertyView $property
     *   If a callback, PropertyView will be set as virtual, sensible default,
     *   hence first callback parameter will be the object, second the property
     *   name.
     *
     * @return $this
     */
    public function propertyRaw(string $name, $property = [], ?string $label = null, bool $hidden = false): self
    {
        $this->dieIfLocked();

        if ($property instanceof PropertyView) {
            $this->properties[$name] = $property->rename($name, $label, ['hidden' => $hidden, 'string_raw' => true]);
        } else if ($property instanceof PropertyDescription) {
            $this->properties[$name] = $property->rename($name, $label, ['hidden' => $hidden, 'string_raw' => true]);
        } else if (\is_array($property)) {
            $options = ['hidden' => $hidden, 'string_raw' => true];
            // Avoid crash where the user wouldn't expect it to crash.
            // When a callback is provided, property must be virtual.
            if (isset($property['callback']) && !\array_key_exists('virtual', $property)) {
                $options['virtual'] = true;
            }
            $this->properties[$name] = new PropertyView($name, null, $options + $property + ['label' => $label] + $this->defaultPropertyView);
        } else if (\is_callable($property)) {
            $this->properties[$name] = new PropertyView($name, null, [
                'callback' => $property,
                'hidden' => $hidden,
                'label' => $label,
                'string_raw' => true,
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
     *
     * @return $this
     */
    public function hiddenProperty(string $name, $property = [], ?string $label = null): self
    {
        $this->property($name, $property, $label, true);

        return $this;
    }

    /**
     * Change property label.
     *
     * @return $this
     */
    public function propertyLabel(string $name, string $label): self
    {
        $this->dieIfLocked();

        $this->propertyLabels[$name] = $label;

        return $this;
    }

    /**
     * @return $this
     */
    public function viewOptions(array $options): self
    {
        $this->dieIfLocked();

        // @todo proper recursive merge.
        $this->viewOptions += $options;

        return $this;
    }

    /**
     * @return $this
     */
    public function template(string $name): self
    {
        $this->dieIfLocked();

        $this->viewOptions['extra']['template'] = $name;

        return $this;
    }

    /**
     * @return $this
     */
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

    /**
     * Get input definition after build.
     *
     * This method will lock the builder.
     */
    public function getViewDefinition(): ViewDefinition
    {
        if (!$this->locked) {
            $this->locked = true;
        }
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

    /**
     * Get view.
     *
     * This method will lock the builder.
     */
    public function getView(): View
    {
        if (!$this->locked) {
            $this->locked = true;
        }
        if ($this->builtView) {
            return $this->builtView;
        }

        $query = $this->getQuery();
        $items = $this->getItems();

        $view = new View($this->getViewDefinition(), $items, $query);
        if ($this->route) {
            $view->setRoute($this->getRoute(), $this->getRouteParameters());
        } else if ($this->request) {
            $view->setRoute($this->request->attributes->get('_route'), $this->request->attributes->get('_route_params'));
        }

        return $this->builtView = $view;
    }

    /**
     * Build the final view builder renderer.
     *
     * This method will lock the builder.
     */
    public function build(): ViewBuilderRenderer
    {
        $this->dieIfLocked();

        $this->eventDispatcher->dispatch(new ViewBuilderEvent($this), ViewBuilderEvent::EVENT_BUILD);

        $this->locked = true;

        return new ViewBuilderRenderer(
            $this->getView(),
            $this->viewRendererRegistry->getViewRenderer($this->rendererName)
        );
    }
}
