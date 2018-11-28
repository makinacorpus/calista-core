<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Calista\Datasource\DatasourceInputDefinition;
use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\View\ViewDefinition;

/**
 * Uses a raw config array
 */
final class ConfigPageDefinition implements PageDefinitionInterface
{
    private $config;
    private $datasource;
    private $id;
    private $input;
    private $viewFactory;

    /**
     * Default constructor
     *
     * @param array $config
     */
    public function __construct(array $config, ViewFactory $viewFactory)
    {
        if (empty($config['datasource'])) {
            throw new \InvalidArgumentException("datasource is missing");
        }
        if (empty($config['view']['view_type'])) {
            throw new \InvalidArgumentException("view:view_type is missing");
        }

        $this->config = $config;
        $this->viewFactory = $viewFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string 
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputDefinition(array $options = []): InputDefinition
    {
        return new DatasourceInputDefinition($this->getDatasource(), $options + ($this->config['input'] ?? []));
    }

    /**
     * {@inheritdoc}
     */
    public function getViewDefinition(array $options = []): ViewDefinition
    {
        return new ViewDefinition($options + $this->config['view']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource(): DatasourceInterface
    {
        return $this->datasource ?? ($this->datasource = $this->viewFactory->getDatasource($this->config['datasource']));
    }
}
