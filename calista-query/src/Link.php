<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * @codeCoverageIgnore
 */
class Link
{
    private ?string $icon = null;
    private $isActive = false;
    private ?string $route = null;
    private array $routeParameters = [];
    private ?string $target = null;
    private ?string $title = null;

    public function __construct(?string $title, ?string $route, array $routeParameters = [], ?string $target = null, bool $isActive = false, ?string $icon = null)
    {
        $this->icon = $icon;
        $this->isActive = $isActive;
        $this->route = $route;
        $this->routeParameters = $routeParameters;
        $this->target = $target;
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function hasTarget(): bool
    {
        return !empty($this->target);
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters ?? [];
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
