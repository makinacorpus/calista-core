<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * @codeCoverageIgnore
 */
trait RouteHolderTrait /* implements RouteHolder */
{
    private ?string $route = null;
    private array $routeParameters = [];

    /**
     * Set current route, useful for views that need to build links.
     *
     * @param string $route
     *   Any kind of route identifier, for pre-Drupal8-like frameworks it would
     *   be an URL path such as 'foo/bar/baz', for Symfony-like frameworks it
     *   rather would be a URL identifier such as 'my_app_foo_page'.
     * @param string[] $parameters
     *   Any kind of forced and protected route parameters that must be restored
     *   in links, in order to ensure we hit the same controller.
     *
     * @return $this
     */
    public function setRoute(string $route, array $parameters = []): self
    {
        $this->route = $route;
        $this->routeParameters = $parameters;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function getRouteParameters(array ...$additional): array
    {
        $ret = $this->routeParameters;

        if ($additional) {
            foreach ($additional as $parameters) {
                foreach ($parameters as $name => $value) {
                    // If key is present, this means that the parameter is protected
                    // and cannot be changed dynamically by calling code.
                    if (!\array_key_exists($name, $ret)) {
                        $ret[$name] = Query::valuesEncode($value);
                    }
                }
            }
        }

        return $ret;
    }
}
