<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\View\AbstractViewRenderer;
use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\Event\ViewEvent;
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

                $enabledFilters[] = new FilterContext($filter, $viewContext);
            }
        }

        return $viewContext->toArray() + [
            'config' => [
                'table_action' => $viewDefinition->getExtraOptionValue('table_action', null),
                'table_sort' => (bool)$viewDefinition->getExtraOptionValue('table_sort', true),
            ],
            'filters' => $enabledFilters,
            'hasPager' => $viewDefinition->isPagerEnabled(),
            'items' => $view->getResult(),
            'pageId' => 'foo', /* $this->getId() */
            'pagerEnabled' => $viewDefinition->isPagerEnabled(),
            'properties' => $view->getNormalizedProperties(),
            'sorts' => $viewDefinition->isSortEnabled() ? $inputDefinition->getAllowedSorts() : [],
            'sortsEnabled' => $viewDefinition->isSortEnabled(),
        ];
    }
}
