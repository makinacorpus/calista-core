<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewRendererRegistry;

use MakinaCorpus\Calista\View\ViewRenderer;
use MakinaCorpus\Calista\View\ViewRendererRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class ContainerViewRendererRegistry implements ViewRendererRegistry, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @param array<string,string> */
    private array $serviceMap = [];

    /**
     * @param array<string,string> $serviceMap
     */
    public function __construct(array $serviceMap)
    {
        $this->serviceMap = $serviceMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewRenderer(string $rendererName): ViewRenderer
    {
        return $this->container->get($this->serviceMap[$rendererName] ?? $rendererName);
    }
}
