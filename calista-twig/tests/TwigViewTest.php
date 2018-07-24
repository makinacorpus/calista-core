<?php

namespace MakinaCorpus\Calista\Tests\View;

use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\Twig\PageExtension;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Calista\View\Html\TwigView;
use MakinaCorpus\Calista\View\Tests\Mock\IntArrayDatasource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class TwigViewTest extends TestCase
{
    /**
     * Create a twig environment with the bare minimum we need
     */
    static public function createTwigEnv(): \Twig_Environment
    {
        $twigEnv = new \Twig_Environment(
            new \Twig_Loader_Array([
                '@calista/page/page-navbar.html.twig' => file_get_contents(dirname(__DIR__) . '/templates/page/page-navbar.html.twig'),
                '@calista/page/page.html.twig' => file_get_contents(dirname(__DIR__) . '/templates/page/page.html.twig'),
            ]),
            [
                'debug' => true,
                'strict_variables' => true,
                'autoescape' => 'html',
                'cache' => false,
                'auto_reload' => null,
                'optimizations' => -1,
            ]
        );

        $twigEnv->addFunction(new \Twig_SimpleFunction('path', function ($route, $routeParameters = []) {
            return $route . '&' . \http_build_query($routeParameters);
        }), ['is_safe' => ['html']]);
        $twigEnv->addFilter(new \Twig_SimpleFilter('trans', function ($string, $params = []) {
            return strtr($string, $params);
        }));
        $twigEnv->addFilter(new \Twig_SimpleFilter('t', function ($string, $params = []) {
            return strtr($string, $params);
        }));
        $twigEnv->addFilter(new \Twig_SimpleFilter('time_diff', function ($value) {
            return (string)$value;
        }));

        $twigEnv->addExtension(new PageExtension(new RequestStack(), PropertyRendererTest::createPropertyRenderer()));

        return $twigEnv;
    }

    /**
     * Tests basics
     */
    public function testBasics()
    {
        $request = new Request([
            'odd_or_even' => 'odd',
            'page' => 3,
            'st' => 'value',
            'by' => Query::SORT_DESC,
        ], [], ['_route' => '_test_route']);

        $datasource = new IntArrayDatasource();
        $inputDefinition = new InputDefinition([
            'limit_default' => 7,
            'filter_list' => $datasource->getFilters(),
        ]);

        $viewDefinition = new ViewDefinition([
            'default_display' => 'page',
            'enabled_filters' => ['odd_or_even'],
            'templates' => [
                'page' => '@calista/page/page.html.twig',
            ],
        ]);
        $view = new TwigView(self::createTwigEnv(), new EventDispatcher());

        // Ensure filters etc
        $filters = $inputDefinition->getFilters();
        $this->assertSame('odd_or_even', \reset($filters)->getField());
        $this->assertSame('Odd or Even', \reset($filters)->getTitle());
//         $visualFilters = $result->getVisualFilters();
//         $this->assertSame('mod3', reset($visualFilters)->getField());
//         $this->assertSame('Modulo 3', reset($visualFilters)->getTitle());

        $query = $inputDefinition->createQueryFromRequest($request);
        $items = $datasource->getItems($query);

        $this->assertCount(7, $items);
        $this->assertSame(3, $query->getPageNumber());
        $this->assertSame(128, $items->getTotalCount());

        // Ensure sorting was OK
        $itemsArray = iterator_to_array($items);
        // FIXMe
        // $this->assertGreaterThan($itemsArray[1], $itemsArray[0]);

        // Build a page, for fun
        $response = $view->renderAsResponse($viewDefinition, $items, $query);
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Tests basics
     */
    public function testDynamicTablePageTemplate()
    {
        $request = new Request([
            'odd_or_even' => 'odd',
            'page' => 3,
            'st' => 'value',
            'by' => Query::SORT_DESC,
        ], [], ['_route' => '_test_route']);

        $datasource = new IntArrayDatasource();
        $inputDefinition = new InputDefinition(['limit_default' => 7]);

        $viewDefinition = new ViewDefinition([
            'default_display' => 'page',
            'enabled_filters' => ['odd_or_even'],
            'templates' => ['page' => '@calista/page/page.html.twig'],
        ]);

        $view = new TwigView(self::createTwigEnv(), new EventDispatcher());

        $query = $inputDefinition->createQueryFromRequest($request);
        $items = $datasource->getItems($query);

        $output = $view->render($viewDefinition, $items, $query);
    }
}
