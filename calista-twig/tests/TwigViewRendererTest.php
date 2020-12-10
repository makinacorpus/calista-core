<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\Tests;

use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\Twig\View\TwigViewRenderer;
use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Calista\View\Tests\Mock\IntArrayDatasource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TwigViewRendererTest extends TestCase
{
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

        $view = new TwigViewRenderer(
            TestFactory::createDefaultTwigBlockRenderer(TestFactory::createTwigEnv()),
            new EventDispatcher()
        );

        // Ensure filters etc
        $filters = $inputDefinition->getFilters();
        self::assertSame('odd_or_even', \reset($filters)->getField());
        self::assertSame('Odd or Even', \reset($filters)->getTitle());

        $query = Query::fromRequest($inputDefinition, $request);
        $items = $datasource->getItems($query);

        self::assertCount(7, $items);
        self::assertSame(3, $query->getCurrentPage());
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

        $view = new TwigViewRenderer(
            TestFactory::createDefaultTwigBlockRenderer(TestFactory::createTwigEnv()),
            new EventDispatcher()
        );

        $query = Query::fromRequest($inputDefinition, $request);
        $items = $datasource->getItems($query);

        /* $output = */ $view->render(new View($viewDefinition, $items, $query));

        self::expectNotToPerformAssertions();
    }
}
