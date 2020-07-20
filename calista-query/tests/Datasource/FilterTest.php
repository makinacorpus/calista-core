<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query\Tests;

use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\QueryFactory;
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
        $filter = new Filter('foo', 'The foo filter');

        self::assertSame('foo', $filter->getField());
        self::assertSame('The foo filter', $filter->getTitle());
        self::assertFalse($filter->isSafe());

        $filter->setChoicesMap([
            'a' => "The A option",
            'b' => "The B option",
            'c' => "The C.. you know the drill",
            'd' => "La réponse D",
        ]);

        self::assertSame(4, $filter->count());
        self::assertCount(4, $filter->getChoicesMap());

        $request = new Request(['foo' => 'a|c'], [], ['_route' => 'where/should/I/go']);
        $query = (new QueryFactory())->fromRequest(new InputDefinition(['filter_list' => [$filter]]), $request);

        $links = $filter->getLinks($query);
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
     * Very simple edge case: when no title, use the field name
     */
    public function testTitleFallback(): void
    {
        $filter = new Filter('my_filter');
        self::assertSame('my_filter', $filter->getTitle());
    }
}
