<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MakinaCorpus\Calista\View\CustomViewBuilder\ClassNameCustomViewBuilderRegistry;

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

    public function __construct(
        ViewRendererRegistry $viewRendererRegistry,
        EventDispatcherInterface $eventDispatcher,
        ?CustomViewBuilderRegistry $customViewBuilderRegistry = null
    ) {
        $this->viewRendererRegistry = $viewRendererRegistry;
        $this->customViewBuilderRegistry = $customViewBuilderRegistry ?? new ClassNameCustomViewBuilderRegistry();
        $this->eventDispatcher = $eventDispatcher;
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
    public function createViewBuilder(?string $name = null, array $options = []): ViewBuilder
    {
        $builder = new ViewBuilder($this->viewRendererRegistry, $this->eventDispatcher);

        if ($name) {
            $this->customViewBuilderRegistry->get($name)->build($builder, $options);
        }

        return $builder;
    }
}
