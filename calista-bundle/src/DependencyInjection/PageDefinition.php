<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\View\ViewDefinition;

/**
 * @deprecated
 * @codeCoverageIgnore
 */
final class PageDefinition
{
    private string $id;
    private array $config;
    private ViewFactory $viewFactory;
    private ?DatasourceInterface $datasource = null;

    /**
     * Default constructor
     *
     * @param array $config
     */
    public function __construct(string $id, array $config, ViewFactory $viewFactory)
    {
        if (empty($config['datasource'])) {
            throw new \InvalidArgumentException("datasource is missing");
        }
        if (empty($config['view']['renderer']) && empty($config['view']['view_type'])) {
            throw new \InvalidArgumentException("view:renderer is missing");
        }

        $this->id = $id;
        $this->config = $config;
        $this->viewFactory = $viewFactory;
    }

    /**
     * Get identifier.
     */
    public function getId(): string 
    {
        return $this->id;
    }

    /**
     * Create configuration.
     *
     * @param mixed[] $options = []
     *   Options overrides from the controller or per site configuration.
     */
    public function getInputDefinition(array $options = []): InputDefinition
    {
        return InputDefinition::datasource($this->getDatasource(), $options + ($this->config['input'] ?? []));
    }

    /**
     * Create view definition for this page.
     *
     * @param mixed[] $options = []
     *   Options overrides from the controller or per site configuration.
     */
    public function getViewDefinition(array $options = []): ViewDefinition
    {
        return new ViewDefinition($options + $this->config['view']);
    }

    /**
     * Get the associated datasource.
     */
    public function getDatasource(): DatasourceInterface
    {
        return $this->datasource ?? ($this->datasource = $this->viewFactory->getDatasource($this->config['datasource']));
    }
}
