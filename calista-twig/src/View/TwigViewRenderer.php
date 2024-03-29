<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use MakinaCorpus\Calista\Query\DefaultFilter;
use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\Attribute\Renderer;
use MakinaCorpus\Calista\View\Event\ViewEvent;
use MakinaCorpus\Calista\View\ViewRenderer\AbstractViewRenderer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uses a view definition and proceed to an html page display via Twig.
 *
 * This renderer understand a few extra options:
 *
 *   - table_sort: (bool) if set to false, clickable table header links for
 *     sorting will be disable (default is true).
 *   - table_action: (callable) must be a callable that take the "item" instance
 *     as first and only parameter: it allows the user to write custom markup
 *     in the very last table column.
 */
#[Renderer(name: 'twig')]
#[Renderer(name: 'twig_page')]
class TwigViewRenderer extends AbstractViewRenderer
{
    private EventDispatcherInterface $dispatcher;
    private DefaultTwigBlockRenderer $blockRenderer;

    public function __construct(DefaultTwigBlockRenderer $blockRenderer, EventDispatcherInterface $dispatcher)
    {
        $this->blockRenderer = $blockRenderer;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function render(View $view): string
    {
        $event = new ViewEvent($this);
        $this->dispatcher->dispatch($event, ViewEvent::EVENT_VIEW);

        $arguments = $this->createTemplateArguments($view);
        $template = $view->getDefinition()->getExtraOptionValue('template');

        return $this->blockRenderer->create($template)->render($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(View $view): Response
    {
        return new Response($this->render($view));
    }

    /**
     * Create template arguments.
     */
    protected function createTemplateArguments(View $view): array
    {
        $viewDefinition = $view->getDefinition();
        $query = $view->getQuery();
        $inputDefinition = $query->getInputDefinition();

        $viewContext = new ViewContext(
            $query,
            $inputDefinition,
            $view,
            $viewDefinition
        );

        // Build allowed filters arrays
        $enabledFilters = [];
        if ($viewDefinition->isFiltersEnabled()) {
            $baseQuery = $inputDefinition->getBaseQuery();

            foreach ($inputDefinition->getFilters() as $filter) {
                \assert($filter instanceof Filter);

                // Only considers filters with choices.
                if ($filter instanceof DefaultFilter) {
                    if (!$filter->hasChoices() && !$filter->isArbitraryInput() && !$filter->isBoolean() && !$filter->isDate()) {
                        continue;
                    }
                }

                $filterName = $filter->getFilterName();

                // Checks that the filter must be displayed.
                if (!$viewDefinition->isFilterEnabled($filterName)) {
                    continue;
                }

                // If the value of the filter is fixed by the base query and is
                // not multiple, it becomes useless to display the filter.
                if (isset($baseQuery[$filterName]) && (!\is_array($baseQuery[$filterName]) || \count($baseQuery[$filterName]) < 2)) {
                    continue;
                }

                $enabledFilters[] = new FilterContext($filter, $viewContext);
            }
        }

        return $viewContext->toArray() + [
            'config' => [
                'table_action' => $viewDefinition->getExtraOptionValue('table_action', null),
                'table_sort' => (bool)$viewDefinition->getExtraOptionValue('table_sort', true),
            ],
            'extended_headers' => $viewDefinition->getExtraOptionValue('table_extended_headers', true),
            'filters' => $enabledFilters,
            'hasPager' => $viewDefinition->isPagerEnabled(),
            'hasGoToPageForm' => $viewDefinition->isGoToPageFormEnabled(),
            'items' => $view->getResult(),
            'pageId' => 'foo', /* $this->getId() */
            'pagerEnabled' => $viewDefinition->isPagerEnabled(),
            'properties' => $view->getNormalizedProperties(),
            'sorts' => $viewDefinition->isSortEnabled() ? $inputDefinition->getAllowedSorts() : [],
            'sortsEnabled' => $viewDefinition->isSortEnabled(),
        ];
    }
}
