<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Facade for users.
 *
 * @codeCoverageIgnore
 */
class ViewManager implements ViewRendererRegistry
{
    private ViewRendererRegistry $viewRendererRegistry;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ViewRendererRegistry $viewRendererRegistry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->viewRendererRegistry = $viewRendererRegistry;
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
     */
    public function createViewBuilder(): ViewBuilder
    {
        return new ViewBuilder($this->viewRendererRegistry, $this->eventDispatcher);
    }
}
