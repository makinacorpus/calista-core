<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\AbstractViewRenderer;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Calista\View\Event\ViewEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Uses a view definition and proceed to an html page display via Twig
 */
class TwigViewRenderer extends AbstractViewRenderer
{
    private bool $debug = false;
    private EventDispatcherInterface $dispatcher;
    private Environment $twig;

    public function __construct(Environment $twig, EventDispatcherInterface $dispatcher)
    {
        $this->twig = $twig;
        $this->debug = $twig->isDebug();
        $this->dispatcher = $dispatcher;
    }

    /**
     * Create the renderer.
     */
    public function createRenderer(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, array $arguments = []): TwigRenderer
    {
        $event = new ViewEvent($this);
        $this->dispatcher->dispatch($event, ViewEvent::EVENT_VIEW);

        $arguments = $this->createTemplateArguments($viewDefinition, $items, $query, $arguments);

        $template = $viewDefinition->getExtraOptionValue('template', '@calista/page/page.html.twig');

        return new TwigRenderer($this->twig, $template, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): string
    {
        return $this->createRenderer($viewDefinition, $items, $query)->render();
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): Response
    {
        return new Response($this->render($viewDefinition, $items, $query));
    }

    /**
     * Create template arguments.
     */
    protected function createTemplateArguments(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, array $arguments = []): array
    {
        $inputDefinition = $query->getInputDefinition();

        // Build allowed filters arrays
        $enabledFilters = [];
        if ($viewDefinition->isFiltersEnabled()) {
            $baseQuery = $inputDefinition->getBaseQuery();
            /** @var \MakinaCorpus\Calista\Query\Filter $filter */
            foreach ($inputDefinition->getFilters() as $filter) {
                // Only considers filters with choices.
                if (!$filter->hasChoices() && !$filter->isArbitraryInput() && !$filter->isBoolean() && !$filter->isDate()) {
                    continue;
                }
                $field = $filter->getField();
                // Checks that the filter must be displayed.
                if (!$viewDefinition->isFilterDisplayed($field)) {
                    continue;
                }
                // If the value of the filter is fixed by the base query and is
                // not multiple, it becomes useless to display the filter.
                if (isset($baseQuery[$field]) && (!\is_array($baseQuery[$field]) || \count($baseQuery[$field]) < 2)) {
                    continue;
                }
                $enabledFilters[] = $filter;
            }
        }

        return [
            'pageId' => 'foo', /* $this->getId() */
            'input' => $inputDefinition,
            'definition' => $viewDefinition,
            'properties' => $this->normalizeProperties($viewDefinition, $items),
            'items' => $items,
            'filters' => $enabledFilters,
            'sorts' => $viewDefinition->isSortEnabled() ? $inputDefinition->getAllowedSorts() : [],
            'sortsEnabled' => $viewDefinition->isSortEnabled(),
            'query' => $query,
            'hasPager' => $viewDefinition->isPagerEnabled(),
            'pagerEnabled' => $viewDefinition->isPagerEnabled(),
        ] + $arguments;
    }
}
