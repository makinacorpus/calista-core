<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\Query\QueryBuilder;
use MakinaCorpus\Calista\View\Event\ViewBuilderEvent;
use MakinaCorpus\Calista\View\ViewBuilder\ViewBuilderRenderer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MakinaCorpus\Calista\Datasource\DatasourceResult;
use MakinaCorpus\Calista\Datasource\Plugin\DatasourcePluginRegistry;
use MakinaCorpus\Calista\Datasource\Plugin\DatasourceResultConverter;

/**
 * @todo unit test me seriously
 */
final class ViewBuilder extends QueryBuilder
{
    private string $rendererName = 'twig';
    private array $viewOptions = [];
    private array $defaultPropertyView = [];
    private array $properties = [];
    private array $propertyLabels = [];
    private ?string $route = null;
    private array $routeParameters = [];

    /**
     * Incomming format has no business purpose for this API, and is purely
     * information, you can use it in any way you wish.
     */
    private ?string $format = null;

    /**
     * This has business purpose for this API, but you can use this parameter
     * to flag builder when they are doing file exports or handle their response
     * themselves.
     */
    private bool $isExport = false;

    private ?ViewDefinition $builtViewDefinition = null;
    private ?View $builtView = null;

    public function __construct(
        private ViewRendererRegistry $viewRendererRegistry,
        EventDispatcherInterface $eventDispatcher,
        private ?ViewBuilderPluginRegistry $viewBuilderPluginRegistry = null,
        private ?DatasourcePluginRegistry $datasourcePluginRegistry = null,
    ) {
        parent::__construct($eventDispatcher);
    }

    /**
     * @return $this
     */
    public function renderer(string $rendererName, array $extraOptions = []): static
    {
        $this->dieIfLocked();

        $this->rendererName = $this->viewOptions['renderer'] = $rendererName;
        $this->viewOptions['extra'] = $extraOptions + ($this->viewOptions['extra'] ?? []);

        return $this;
    }

    /**
     * Set filename for when sending file responses.
     *
     * File may or may not include an extension. If it doesn't, behavior of
     * adding one is up to the renderer. All default renderer will add the
     * correct file extension if none provided.
     *
     * @return $this
     */
    public function filename(?string $filename): static
    {
        $this->dieIfLocked();

        $this->extra('filename', $filename);

        return $this;
    }

    /**
     * Get default format.
     *
     * Format is purely informational, has already been passed to plugins and
     * custom view builder before you access this property, still it can be
     * useful to access within your controllers.
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * Set default format.
     *
     * Format is incomming from ViewManager::createViewBuilder() method
     * parameters or set by plugins.
     *
     * @return $this
     */
    public function format(?string $format): static
    {
        $this->dieIfLocked();

        $this->format = $format;

        return $this;
    }

    /**
     * Is this builder a data export.
     *
     * This is purely informational, has already been passed to plugins and
     * custom view builder before you access this property, still it can be
     * useful to access within your controllers.
     */
    public function isExport(): bool
    {
        return $this->isExport;
    }

    /**
     * Toggle this builder bgin a data export.
     *
     * @return $this
     */
    public function export(bool $toggle = true): static
    {
        $this->dieIfLocked();

        $this->isExport = $toggle;

        return $this;
    }

    /**
     * Get extra option value.
     */
    public function getExtra(string $name): mixed
    {
        return $this->viewOptions['extra'][$name] ?? null;
    }

    /**
     * Add extra option value for view renderer.
     *
     * @return $this
     */
    public function extra(string $name, $value): static
    {
        $this->dieIfLocked();

        $this->viewOptions['extra'][$name] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function enablePager(bool $enable = true, string $parameterName = 'page'): static
    {
        $this->dieIfLocked();

        $this->inputOptions['pager_enable'] = true;
        $this->inputOptions['pager_param'] = $parameterName;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableFilters(string ... $filterNames): static
    {
        $this->dieIfLocked();

        foreach ($filterNames as $filterName) {
            // @todo Update ViewDefinition to handle a graylist were filters
            //   can be explicitely set to false, where defaults don't need
            //   to be added to the list.
            if (!\in_array($filterName, $this->viewOptions['enabled_filters'] ?? [])) {
                $this->viewOptions['enabled_filters'][] = $filterName;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function disableFilter(string $filterName): static
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
    public function showPager(bool $enable = true): static
    {
        $this->dieIfLocked();

        $this->viewOptions['show_pager'] = $enable;

        return $this;
    }

    /**
     * @return $this
     */
    public function showFilters(bool $enable = true): static
    {
        $this->dieIfLocked();

        $this->viewOptions['show_filters'] = $enable;

        return $this;
    }

    /**
     * @return $this
     */
    public function showSort(bool $enable = true): static
    {
        $this->dieIfLocked();

        $this->viewOptions['show_sort'] = $enable;

        return $this;
    }

    /**
     * @return $this
     */
    public function defaultPropertyView(array $options): static
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
    public function preload($callback): static
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
    public function property(string $propertyName, $property = [], ?string $label = null, bool $hidden = false): static
    {
        $this->dieIfLocked();

        if ($property instanceof PropertyView) {
            $this->properties[$propertyName] = $property->rename($propertyName, $label, ['hidden' => $hidden]);
        } else if ($property instanceof PropertyDescription) {
            $this->properties[$propertyName] = $property->rename($propertyName, $label, ['hidden' => $hidden]);
        } else if (\is_array($property)) {
            $this->properties[$propertyName] = new PropertyView($propertyName, null, ['hidden' => $hidden] + $property + ['label' => $label] + $this->defaultPropertyView);
        } else if (\is_callable($property)) {
            $this->properties[$propertyName] = new PropertyView($propertyName, null, [
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
    public function propertyRaw(string $propertyName, $property = [], ?string $label = null, bool $hidden = false): static
    {
        $this->dieIfLocked();

        if ($property instanceof PropertyView) {
            $this->properties[$propertyName] = $property->rename($propertyName, $label, ['hidden' => $hidden, 'string_raw' => true]);
        } else if ($property instanceof PropertyDescription) {
            $this->properties[$propertyName] = $property->rename($propertyName, $label, ['hidden' => $hidden, 'string_raw' => true]);
        } else if (\is_array($property)) {
            $options = ['hidden' => $hidden, 'string_raw' => true];
            // Avoid crash where the user wouldn't expect it to crash.
            // When a callback is provided, property must be virtual.
            if (isset($property['callback']) && !\array_key_exists('virtual', $property)) {
                $options['virtual'] = true;
            }
            $this->properties[$propertyName] = new PropertyView($propertyName, null, $options + $property + ['label' => $label] + $this->defaultPropertyView);
        } else if (\is_callable($property)) {
            $this->properties[$propertyName] = new PropertyView($propertyName, null, [
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
    public function hiddenProperty(string $propertyName, $property = [], ?string $label = null): static
    {
        $this->property($propertyName, $property, $label, true);

        return $this;
    }

    /**
     * Change property label.
     *
     * @return $this
     */
    public function propertyLabel(string $propertyName, string $label): static
    {
        $this->dieIfLocked();

        $this->propertyLabels[$propertyName] = $label;

        return $this;
    }

    /**
     * @return $this
     */
    public function viewOptions(array $options): static
    {
        $this->dieIfLocked();

        // @todo proper recursive merge.
        $this->viewOptions += $options;

        return $this;
    }

    /**
     * @return $this
     */
    public function template(string $templateName): static
    {
        $this->dieIfLocked();

        $this->viewOptions['extra']['template'] = $templateName;

        return $this;
    }

    /**
     * @return $this
     */
    public function route(string $route, array $parameters = []): static
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

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
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
            foreach ($this->properties as $propertyName => $property) {
                \assert($property instanceof PropertyView || $property instanceof PropertyDescription);

                $newLabel = $this->propertyLabels[$propertyName] ?? null;
                if ($newLabel) {
                    $options['properties'][$propertyName] = $property->rename($propertyName, $newLabel);
                } else {
                    $options['properties'][$propertyName] = $property;
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

        if (!$items instanceof DatasourceResult && $this->datasourcePluginRegistry) {
            foreach ($this->datasourcePluginRegistry->getResultConverters() as $converter) {
                \assert($converter instanceof DatasourceResultConverter);
                if (null !== ($candidate = $converter->convert($items))) {
                    $items = $candidate;
                    break;
                }
            }
        }

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

        if ($this->viewBuilderPluginRegistry) {
            foreach ($this->viewBuilderPluginRegistry->all() as $plugin) {
                \assert($plugin instanceof ViewBuilderPlugin);
                $plugin->preBuildView($this);
            }
        }

        $this->eventDispatcher->dispatch(new ViewBuilderEvent($this), ViewBuilderEvent::EVENT_BUILD);

        $this->locked = true;

        return new ViewBuilderRenderer(
            $this->getView(),
            $this->viewRendererRegistry->getViewRenderer($this->rendererName)
        );
    }
}
