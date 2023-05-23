<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

interface ViewRendererRegistry
{
    /**
     * Get view renderer.
     */
    public function getViewRenderer(string $rendererName): ViewRenderer;
}
