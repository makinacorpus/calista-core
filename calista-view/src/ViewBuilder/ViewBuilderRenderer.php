<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewBuilder;

use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\ViewRenderer;
use Symfony\Component\HttpFoundation\Response;

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
