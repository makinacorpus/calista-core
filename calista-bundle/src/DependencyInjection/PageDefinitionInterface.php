<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\View\ViewDefinition;

/**
 * A page type is a re-usable specific page definition, that will allow you to,
 * once registered as a container service, benefit from AJAX capabilities of
 * the HTML pages.
 *
 * It also allows you to define once then re-use specific pages at various
 * places on your site.
 */
interface PageDefinitionInterface
{
    /**
     * Get identifier
     */
    public function getId(): string;

    /**
     * Create configuration
     *
     * @param mixed[] $options = []
     *   Options overrides from the controller or per site configuration
     */
    public function getInputDefinition(array $options = []): InputDefinition;

    /**
     * Create view definition for this page
     *
     * @param mixed[] $options = []
     *   Options overrides from the controller or per site configuration
     */
    public function getViewDefinition(array $options = []): ViewDefinition;

    /**
     * Get the associated datasource
     */
    public function getDatasource(): DatasourceInterface;
}
