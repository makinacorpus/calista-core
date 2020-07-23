<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

/**
 * Facade for users.
 *
 * @codeCoverageIgnore
 */
class ViewManager implements ViewRendererRegistry
{
    private ViewRendererRegistry $viewRendererRegistry;

    public function __construct(ViewRendererRegistry $viewRendererRegistry)
    {
        $this->viewRendererRegistry = $viewRendererRegistry;
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
        return new ViewBuilder($this->viewRendererRegistry);
    }
}
