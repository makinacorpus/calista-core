<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\View\AbstractViewRenderer;
use MakinaCorpus\Calista\View\View;
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
    public function createRenderer(View $view): TwigRenderer
    {
        $event = new ViewEvent($this);
        $this->dispatcher->dispatch($event, ViewEvent::EVENT_VIEW);

        $arguments = $this->createTemplateArguments($view);

        $template = $view->getDefinition()->getExtraOptionValue('template', '@calista/page/page.html.twig');

        return new TwigRenderer($this->twig, $template, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function render(View $view): string
    {
        return $this->createRenderer($view)->render();
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

                $enabledFilters[] = $filter;
            }
        }

        return [
            'definition' => $viewDefinition,
            'filters' => $enabledFilters,
            'hasPager' => $viewDefinition->isPagerEnabled(),
            'input' => $inputDefinition,
            'items' => $view->getResult(),
            'pageId' => 'foo', /* $this->getId() */
            'pagerEnabled' => $viewDefinition->isPagerEnabled(),
            'properties' => $view->getNormalizedProperties(),
            'query' => $query,
            'sorts' => $viewDefinition->isSortEnabled() ? $inputDefinition->getAllowedSorts() : [],
            'sortsEnabled' => $viewDefinition->isSortEnabled(),
            'view' => $view,
        ];
    }
}
