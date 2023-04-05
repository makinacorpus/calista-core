<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewRendererRegistry;

use MakinaCorpus\Calista\View\ViewRenderer;
use MakinaCorpus\Calista\View\ViewRendererRegistry;

final class ArrayViewRendererRegistry implements ViewRendererRegistry
{
    /** @param array<string,ViewRenderer> */
    private array $viewRenderers = [];

    /**
     * @param array<string,ViewRenderer> $viewRenderers
     */
    public function __construct(array $viewRenderers)
    {
        $this->viewRenderers = $viewRenderers;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewRenderer(string $rendererName): ViewRenderer
    {
        $ret = $this->viewRenderers[$rendererName] ?? null;

        if (!$ret) {
            throw new \InvalidArgumentException(\sprintf("View renderer with name '%s' does not exist.", $rendererName));
        }

        return $ret;
    }
}
