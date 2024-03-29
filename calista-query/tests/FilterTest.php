<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query\Tests;

use MakinaCorpus\Calista\Query\DefaultFilter;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\View;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the page query parsing
 */
final class FilterTest extends TestCase
{
    /**
     * Tests basics
     */
    public function testBasics(): void
    {
        $filter = new DefaultFilter('foo', 'The foo filter');

        self::assertSame('foo', $filter->getFilterName());
        self::assertSame('The foo filter', $filter->getTitle());
        self::assertFalse($filter->isSafe());

        $filter->setChoicesMap([
            'a' => "The A option",
            'b' => "The B option",
            'c' => "The C.. you know the drill",
            'd' => "La réponse D",
        ]);

        self::assertCount(4, $filter->getChoicesMap());

        $request = new Request(['foo' => 'a|c']);
        $query = Query::fromRequest(new InputDefinition(['filter_list' => [$filter]]), $request);
        $view = View::empty()->setRoute('where/should/I/go');

        $links = $filter->getLinks($query, $view);
        self::assertCount(4, $links);

        // Get individual links, they should be ordered
        $aLink = $links[0];
        $bLink = $links[1];
        $cLink = $links[2];
        $dLink = $links[3];

        // Just for fun, test the link class
        self::assertSame($aLink->getRouteParameters(), $aLink->getRouteParameters());
        self::assertSame('The A option', $aLink->getTitle());
        self::assertSame('where/should/I/go', $aLink->getRoute());

        // Active state
        self::assertTrue($aLink->isActive());
        self::assertFalse($bLink->isActive());
        self::assertTrue($cLink->isActive());
        self::assertFalse($dLink->isActive());

        // Should test the parameters too, but I'm too lazy.
    }

    /**
     * Very simple edge case: when no title, use the filter name
     */
    public function testTitleFallback(): void
    {
        $filter = new DefaultFilter('my_filter');
        self::assertSame('my_filter', $filter->getTitle());
    }
}
