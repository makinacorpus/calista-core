<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\Controller;

use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\PageDefinition;
use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\ViewFactory;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PageRenderer
{
    private ViewFactory $viewFactory;

    public function __construct(ViewFactory $viewFactory)
    {
        $this->viewFactory = $viewFactory;
    }

    /**
     * Render a page from definition.
     *
     * @param string|PageDefinition $page
     *   Page class or identifier.
     * @param Request $request
     *   Incomming request.
     * @param array $inputOptions
     *   Overrides for the input options.
     */
    public function renderPage($name, Request $request, array $inputOptions = [], array $viewOptions = []): string
    {
        if ($name instanceof PageDefinition) {
            $page = $name;
        } else {
            $page = $this->viewFactory->getPageDefinition($name);
        }

        $viewDefinition = $page->getViewDefinition($viewOptions);
        $viewRenderer = $this->viewFactory->getView($viewDefinition->getRendererName());

        $inputDefinition = $page->getInputDefinition($inputOptions);
        $query = Query::fromRequest($inputDefinition, $request);
        $items = $page->getDatasource()->getItems($query);

        $view = new View($viewDefinition, $items, $query);
        $view->setRoute($request->attributes->get('_route'), $request->attributes->get('_route_params'));

        return $viewRenderer->render($view);
    }

    /**
     * Render a page from definition.
     *
     * Using a response for rendering is the right choice when you generate
     * outputs with large datasets, it allows the view to control the response
     * type hence use a streamed response whenever possible.
     *
     * @param string|PageDefinition $page
     *   Page class or identifier.
     * @param Request $request
     *   Incomming request.
     * @param array $inputOptions
     *   Overrides for the input options.
     */
    public function renderPageResponse($name, Request $request, array $inputOptions = [], array $viewOptions = []): Response
    {
        if ($name instanceof PageDefinition) {
            $page = $name;
        } else {
            $page = $this->viewFactory->getPageDefinition($name);
        }

        $viewDefinition = $page->getViewDefinition($viewOptions);
        $viewRenderer = $this->viewFactory->getView($viewDefinition->getRendererName());

        $inputDefinition = $page->getInputDefinition($inputOptions);
        $query = $query = Query::fromRequest($inputDefinition, $request);
        $items = $page->getDatasource()->getItems($query);

        $view = new View($viewDefinition, $items, $query);
        $view->setRoute($request->attributes->get('_route'), $request->attributes->get('_route_params'));

        return $viewRenderer->renderAsResponse(new View($viewDefinition, $items, $query));
    }
}
