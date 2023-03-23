<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\View\CustomViewBuilder\ClassNameCustomViewBuilderRegistry;
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

    public function __construct(
        ViewRendererRegistry $viewRendererRegistry,
        EventDispatcherInterface $eventDispatcher,
        ?CustomViewBuilderRegistry $customViewBuilderRegistry = null,
        ?ViewBuilderPluginRegistry $viewBuilderPluginRegistry = null
    ) {
        $this->viewRendererRegistry = $viewRendererRegistry;
        $this->customViewBuilderRegistry = $customViewBuilderRegistry ?? new ClassNameCustomViewBuilderRegistry();
        $this->eventDispatcher = $eventDispatcher;
        $this->viewBuilderPluginRegistry = $viewBuilderPluginRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewRenderer(string $name): ViewRenderer
    {
        return $this->viewRendererRegistry->getViewRenderer($name);
    }

    /**
     * Create view builder.
     *
     * @param null|string $name
     *   Pass here the custom view builder name if you wish to use one existing.
     * @param array<string, null|bool|int|string> $options
     *   Key-value pairs of options for this custom view builder.
     */
    public function createViewBuilder(?string $name = null, array $options = [], ?string $format = null): ViewBuilder
    {
        $builder = new ViewBuilder($this->viewRendererRegistry, $this->eventDispatcher);
        $builder->format($format);

        foreach ($this->viewBuilderPluginRegistry->all() as $plugin) {
            \assert($plugin instanceof ViewBuilderPlugin);
            $plugin->preBuild($builder, $options, $format);
        }

        if ($name) {
            $this->customViewBuilderRegistry->get($name)->build($builder, $options, $format);
        }

        foreach ($this->viewBuilderPluginRegistry->all() as $plugin) {
            \assert($plugin instanceof ViewBuilderPlugin);
            $plugin->postBuild($builder, $options, $format);
        }

        return $builder;
    }
}
