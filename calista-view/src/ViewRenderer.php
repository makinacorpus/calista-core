<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use Symfony\Component\HttpFoundation\Response;

/**
 * View renderer: stateless object that will display given items accordingly
 * to given view definition.
 */
interface ViewRenderer
{
    /**
     * Render the view.
     */
    public function render(View $view): string;

    /**
     * Render the view.
     */
    public function renderInStream(View $view, $resource): void;

    /**
     * Render the view.
     */
    public function renderInFile(View $view, string $filename): void;

    /**
     * Render the view as a response.
     */
    public function renderAsResponse(View $view): Response;
}
