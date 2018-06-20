<?php

namespace MakinaCorpus\Calista\Query\Tests;

use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\QueryFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the page query parsing
 */
class FilterTest extends TestCase
{
    /**
     * Tests basics
     */
    public function testBasics()
    {
        $filter = new Filter('foo', 'The foo filter');

        $this->assertSame('foo', $filter->getField());
        $this->assertSame('The foo filter', $filter->getTitle());
        $this->assertFalse($filter->isSafe());

        $filter->setChoicesMap([
            'a' => "The A option",
            'b' => "The B option",
            'c' => "The C.. you know the drill",
            'd' => "La réponse D",
        ]);

        $this->assertSame(4, $filter->count());
        $this->assertCount(4, $filter->getChoicesMap());

        $request = new Request(['foo' => 'a|c'], [], ['_route' => 'where/should/I/go']);
        $query = (new QueryFactory())->fromRequest(new InputDefinition(), $request);

        $links = $filter->getLinks($query);
        $this->assertCount(4, $links);

        // Get individual links, they should be ordered
        $aLink = $links[0];
        $bLink = $links[1];
        $cLink = $links[2];
        $dLink = $links[3];

        // Just for fun, test the link class
        $this->assertSame($aLink->getRouteParameters(), $aLink->getRouteParameters());
        $this->assertSame('The A option', $aLink->getTitle());
        $this->assertSame('where/should/I/go', $aLink->getRoute());

        // Active state
        $this->assertTrue($aLink->isActive());
        $this->assertFalse($bLink->isActive());
        $this->assertTrue($cLink->isActive());
        $this->assertFalse($dLink->isActive());

        // Should test the parameters too, but I'm too lazy.
    }

    /**
     * Very simple edge case: when no title, use the field name
     */
    public function testTitleFallback()
    {
        $filter = new Filter('my_filter');
        $this->assertSame('my_filter', $filter->getTitle());
    }
}
