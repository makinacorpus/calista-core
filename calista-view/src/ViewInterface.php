<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Query;
use Symfony\Component\HttpFoundation\Response;

/**
 * Represents a view, anything that can be displayed from datasource data
 */
interface ViewInterface
{
    /**
     * Render the view
     *
     * @param ViewDefinition $viewDefinition
     *   The view configuration
     * @param DatasourceResultInterface $items
     *   Items from a datasource
     * @param Query $query
     *   Incoming query that was given to the datasource
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): string;

    /**
     * Render the view as a response
     *
     * @param ViewDefinition $viewDefinition
     *   The view configuration
     * @param DatasourceResultInterface $items
     *   Items from a datasource
     * @param Query $query
     *   Incoming query that was given to the datasource
     *
     * @return Response
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): Response;
}
