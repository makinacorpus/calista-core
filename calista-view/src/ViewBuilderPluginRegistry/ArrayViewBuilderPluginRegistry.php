<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewBuilderPluginRegistry;

use MakinaCorpus\Calista\View\ViewBuilderPluginRegistry;

final class ArrayViewBuilderPluginRegistry implements ViewBuilderPluginRegistry
{
    /** @param array<string,ViewBuilderPlugin */
    private array $viewBuilderPlugins = [];

    /**
     * @param array<string,ViewBuilderPlugin> $viewBuilderPlugins
     */
    public function __construct(array $viewBuilderPlugins)
    {
        $this->viewBuilderPlugins = $viewBuilderPlugins;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        return $this->viewBuilderPlugins;
    }
}
