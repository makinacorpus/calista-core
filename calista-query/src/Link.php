<?php

namespace MakinaCorpus\Calista\Query;

/**
 * @codeCoverageIgnore
 */
class Link
{
    private $title;
    private $route;
    private $routeParameters;
    private $isActive = false;
    private $icon;

    public function __construct(string $title, string $route, array $routeParameters = [], bool $isActive = false, string $icon = null)
    {
        $this->title = $title;
        $this->route = $route;
        $this->routeParameters = $routeParameters;
        $this->isActive = $isActive;
        $this->icon = $icon;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function getRoute(): string
    {
        return $this->route ?? '';
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters ?? [];
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getIcon(): string
    {
        return $this->icon ?? '';
    }
}
