<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Tests\View;

use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\Twig\Extension\PageExtension;
use MakinaCorpus\Calista\Twig\View\TwigViewRenderer;
use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Calista\View\Tests\Mock\IntArrayDatasource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Loader\ArrayLoader;

final class TwigViewRendererTest extends TestCase
{
    /**
     * Create a twig environment with the bare minimum we need
     */
    static public function createTwigEnv(): Environment
    {
        $twigEnv = new Environment(
            new ArrayLoader([
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

        $twigEnv->addFunction(new TwigFunction('path', function ($route, $routeParameters = []) {
            return $route . '&' . \http_build_query($routeParameters);
        }), ['is_safe' => ['html']]);
        $twigEnv->addFilter(new TwigFilter('trans', function ($string, $params = []) {
            return \strtr($string, $params);
        }));
        $twigEnv->addFilter(new TwigFilter('t', function ($string, $params = []) {
            return \strtr($string, $params);
        }));
        $twigEnv->addFilter(new TwigFilter('time_diff', function ($value) {
            return (string)$value;
        }));

        $twigEnv->addExtension(new PageExtension(new RequestStack(), PropertyRendererTest::createPropertyRenderer()));

        return $twigEnv;
    }

    /**
     * Tests basics
     */
    public function testBasics(): void
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
            'enabled_filters' => ['odd_or_even'],
        ]);
        $view = new TwigViewRenderer(self::createTwigEnv(), new EventDispatcher());

        // Ensure filters etc
        $filters = $inputDefinition->getFilters();
        self::assertSame('odd_or_even', \reset($filters)->getField());
        self::assertSame('Odd or Even', \reset($filters)->getTitle());

        $query = Query::fromRequest($inputDefinition, $request);
        $items = $datasource->getItems($query);

        self::assertCount(7, $items);
        self::assertSame(3, $query->getPageNumber());
        self::assertSame(128, $items->getTotalCount());

        // Ensure sorting was OK
        $itemsArray = \iterator_to_array($items);
        self::assertNotNull($itemsArray);

        // Build a page, for fun
        $response = $view->renderAsResponse(new View($viewDefinition, $items, $query));
        self::assertInstanceOf(Response::class, $response);
    }

    /**
     * Tests basics
     */
    public function testDynamicTablePageTemplate(): void
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
            'enabled_filters' => ['odd_or_even'],
        ]);

        $view = new TwigViewRenderer(self::createTwigEnv(), new EventDispatcher());

        $query = Query::fromRequest($inputDefinition, $request);
        $items = $datasource->getItems($query);

        /* $output = */ $view->render(new View($viewDefinition, $items, $query));

        self::expectNotToPerformAssertions();
    }
}
