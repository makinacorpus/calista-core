<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\Controller;

use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\PageDefinitionInterface;
use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\ViewFactory;
use MakinaCorpus\Calista\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Yes, it renders pages.
 */
final class PageRenderer
{
    private ViewFactory $viewFactory;

    public function __construct(ViewFactory $viewFactory)
    {
        $this->viewFactory = $viewFactory;
    }

    /**
     * Render a page from definition
     *
     * @param string|PageDefinitionInterface $page
     *   Page class or identifier
     * @param Request $request
     *   Incomming request
     * @param array $inputOptions
     *   Overrides for the input options
     */
    public function renderPage($name, Request $request, array $inputOptions = [], array $viewOptions = []): string
    {
        if ($name instanceof PageDefinitionInterface) {
            $page = $name;
        } else {
            $page = $this->viewFactory->getPageDefinition($name);
        }

        $viewDefinition = $page->getViewDefinition($viewOptions);
        $view = $this->viewFactory->getView($viewDefinition->getViewType());

        $query = $page->getInputDefinition($inputOptions)->createQueryFromRequest($request);
        $items = $page->getDatasource()->getItems($query);

        return $view->render(new View($viewDefinition, $items, $query));
    }

    /**
     * Render a page from definition
     *
     * Using a response for rendering is the right choice when you generate
     * outputs with large datasets, it allows the view to control the response
     * type hence use a streamed response whenever possible.
     *
     * @param string|PageDefinitionInterface $page
     *   Page class or identifier
     * @param Request $request
     *   Incomming request
     * @param array $inputOptions
     *   Overrides for the input options
     */
    public function renderPageResponse($name, Request $request, array $inputOptions = [], array $viewOptions = []): Response
    {
        if ($name instanceof PageDefinitionInterface) {
            $page = $name;
        } else {
            $page = $this->viewFactory->getPageDefinition($name);
        }

        $viewDefinition = $page->getViewDefinition($viewOptions);
        $view = $this->viewFactory->getView($viewDefinition->getViewType());

        $query = $page->getInputDefinition($inputOptions)->createQueryFromRequest($request);
        $items = $page->getDatasource()->getItems($query);

        return $view->renderAsResponse(new View($viewDefinition, $items, $query));
    }
}
