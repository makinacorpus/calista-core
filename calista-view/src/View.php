<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Query;
use Symfony\Component\HttpFoundation\Response;

/**
 * Volatile data value object that you will use to proceed to final rendering.
 */
interface View
{
    /**
     * Render the view.
     *
     * @param ViewDefinition $viewDefinition
     *   The view configuration.
     * @param DatasourceResultInterface $items
     *   Items from a datasource.
     * @param Query $query
     *   Incoming query that was given to the datasource.
     */
    public function render($items): string;

    /**
     * Render the view.
     *
     * @param ViewDefinition $viewDefinition
     *   The view configuration.
     * @param DatasourceResultInterface $items
     *   Items from a datasource.
     * @param Query $query
     *   Incoming query that was given to the datasource.
     * @param resource $resource
     *   A valid open stream, at least opened in "w" mode.
     */
    public function renderInStream($items, $resource): void;

    /**
     * Render the view.
     *
     * @param ViewDefinition $viewDefinition
     *   The view configuration.
     * @param DatasourceResultInterface $items
     *   Items from a datasource.
     * @param Query $query
     *   Incoming query that was given to the datasource.
     * @param resource $resource
     *   A valid open stream, at least opened in "w" mode.
     */
    public function renderInFile($items, string $filename): void;

    /**
     * Render the view as a response.
     *
     * @param ViewDefinition $viewDefinition
     *   The view configuration.
     * @param DatasourceResultInterface $items
     *   Items from a datasource.
     * @param Query $query
     *   Incoming query that was given to the datasource.
     *
     * @return Response
     */
    public function renderAsResponse($items): Response;
}
