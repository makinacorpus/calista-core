<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\Plugin\DatasourcePluginRegistry;
use MakinaCorpus\Calista\Datasource\Plugin\DatasourcePluginRegistry\ArrayDatasourcePluginRegistry;
use MakinaCorpus\Calista\View\CustomViewBuilder\ClassNameCustomViewBuilderRegistry;
use MakinaCorpus\Calista\View\ViewBuilderPluginRegistry\ArrayViewBuilderPluginRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Facade for users.
 *
 * @codeCoverageIgnore
 */
class ViewManager implements ViewRendererRegistry
{
    private ViewRendererRegistry $viewRendererRegistry;
    private CustomViewBuilderRegistry $customViewBuilderRegistry;
    private EventDispatcherInterface $eventDispatcher;
    private ViewBuilderPluginRegistry $viewBuilderPluginRegistry;
    private DatasourcePluginRegistry $datasourcePluginRegistry;

    public function __construct(
        ViewRendererRegistry $viewRendererRegistry,
        EventDispatcherInterface $eventDispatcher,
        ?CustomViewBuilderRegistry $customViewBuilderRegistry = null,
        ?ViewBuilderPluginRegistry $viewBuilderPluginRegistry = null,
        ?DatasourcePluginRegistry $datasourcePluginRegistry = null
    ) {
        $this->viewRendererRegistry = $viewRendererRegistry;
        $this->customViewBuilderRegistry = $customViewBuilderRegistry ?? new ClassNameCustomViewBuilderRegistry();
        $this->eventDispatcher = $eventDispatcher;
        $this->viewBuilderPluginRegistry = $viewBuilderPluginRegistry ?? new ArrayViewBuilderPluginRegistry([]);
        $this->datasourcePluginRegistry = $datasourcePluginRegistry ?? new ArrayDatasourcePluginRegistry([]);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewRenderer(string $rendererName): ViewRenderer
    {
        return $this->viewRendererRegistry->getViewRenderer($rendererName);
    }

    /**
     * Create view builder.
     *
     * @param null|string $builderName
     *   Pass here the custom view builder name if you wish to use one existing.
     * @param array<string, null|bool|int|string> $options
     *   Key-value pairs of options for this custom view builder.
     */
    public function createViewBuilder(?string $builderName = null, array $options = [], ?string $format = null): ViewBuilder
    {
        $builder = new ViewBuilder(
            $this->viewRendererRegistry,
            $this->eventDispatcher,
            $this->viewBuilderPluginRegistry,
            $this->datasourcePluginRegistry,
        );

        $builder->format($format);

        foreach ($this->viewBuilderPluginRegistry->all() as $plugin) {
            \assert($plugin instanceof ViewBuilderPlugin);
            $plugin->preBuild($builder, $options, $format);
        }

        if ($builderName) {
            $this->customViewBuilderRegistry->get($builderName)->build($builder, $options, $format);
        }

        foreach ($this->viewBuilderPluginRegistry->all() as $plugin) {
            \assert($plugin instanceof ViewBuilderPlugin);
            $plugin->postBuild($builder, $options, $format);
        }

        return $builder;
    }
}
