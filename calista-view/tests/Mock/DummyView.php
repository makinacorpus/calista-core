<?php

namespace MakinaCorpus\Calista\View\Tests\Mock;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\AbstractView;
use MakinaCorpus\Calista\View\ViewDefinition;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used to test the abstract view
 */
class DummyView extends AbstractView
{
    /**
     * Passthrougth for normalizeProperties().
     */
    public function normalizePropertiesPassthrought(ViewDefinition $viewDefinition, DatasourceResultInterface $items): array
    {
        return $this->normalizeProperties($viewDefinition, $items);
    }

    /**
     * {@inheritdoc}
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): Response
    {
        return new Response();
    }
}
