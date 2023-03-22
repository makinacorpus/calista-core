<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

interface ViewBuilderPluginRegistry
{
    /**
     * Get all view builder plugins.
     *
     * @return ViewBuilderPlugin[]
     */
    public function all(): iterable;
}
