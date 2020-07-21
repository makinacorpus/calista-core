<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\View\ViewRenderer;
use MakinaCorpus\Calista\View\ViewRendererRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @codeCoverageIgnore
 * @deprecated
 *   Please do not use this anymore. Legal variant is to use the view manager
 *   and use the View class or the ViewBuilder instead now. This class only
 *   exists as a bridge and will be removed soon.
 * @see \MakinaCorpus\Calista\View\View
 * @see \MakinaCorpus\Calista\View\ViewManager
 */
final class ViewFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private ViewRendererRegistry $viewRendererRegistry;
    private array $pageDefinitions = [];

    public function __construct(ViewRendererRegistry $viewRendererRegistry, array $pageDefinitions = [])
    {
        $this->viewRendererRegistry = $viewRendererRegistry;
        $this->pageDefinitions = $pageDefinitions;
    }

    /**
     * Get datasource.
     */
    public function getDatasource(string $name): DatasourceInterface
    {
        @\trigger_error(\sprintf("You should not be using %s", self::class), E_USER_DEPRECATED);

        return $this->container->get($name);
    }

    /**
     * Get page definition.
     */
    public function getPageDefinition(string $name): PageDefinition
    {
        @\trigger_error(\sprintf("You should not be using %s", self::class), E_USER_DEPRECATED);

        $config = $this->pageDefinitions[$name] ?? null;

        if (!$config) {
            throw new \InvalidArgumentException(\sprintf("Page with name '%s' does not exist.", $name));
        }

        return new PageDefinition($config['id'] ?? $name, $config, $this);
    }

    /**
     * Get view.
     */
    public function getView(string $name): ViewRenderer
    {
        @\trigger_error(\sprintf("You should not be using %s", self::class), E_USER_DEPRECATED);

        return $this->viewRendererRegistry->getViewRenderer($name);
    }
}
