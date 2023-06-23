<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewRendererRegistry;

use MakinaCorpus\Calista\View\ViewRenderer;
use MakinaCorpus\Calista\View\ViewRendererRegistry;
use Psr\Container\ContainerInterface;

final class ContainerViewRendererRegistry implements ViewRendererRegistry
{
    /**
     * @param array<string,string> $serviceMap
     */
    public function __construct(
        private array $serviceMap,
        private ?ContainerInterface $container = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getViewRenderer(string $rendererName): ViewRenderer
    {
        if (!$this->container) {
            throw new \LogicException("Uninitialized object, missing container.");
        }

        return $this->container->get($this->serviceMap[$rendererName] ?? $rendererName);
    }
}
