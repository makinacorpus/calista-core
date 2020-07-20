<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query\Tests;

use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\Query\QueryFactory;
use MakinaCorpus\Calista\Query\QueryStringParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the page query parsing
 */
final class QueryTest extends TestCase
{
    /**
     * Tests basics
     */
    public function testSortHandling(): void
    {
        $request = new Request(['st' => 'b', 'by' => 'asc', 'foo' => 'barr'], [], ['_route' => 'my_route']);
        $inputDefinition = new InputDefinition(['sort_allowed_list' => ['a', 'b', 'c']]);
        $query = (new QueryFactory())->fromRequest($inputDefinition, $request);

        self::assertSame(3, \count($inputDefinition->getAllowedSorts()));
        self::assertSame(Query::SORT_DESC, $inputDefinition->getDefaultSortOrder());
        self::assertSame('a', $inputDefinition->getDefaultSortField());
        self::assertSame(Query::SORT_ASC, $query->getSortOrder());
        self::assertSame('b', $query->getSortField());
    }

    /**
     * Tests basic accesors
     */
    public function testQueryBasics(): void
    {
        $request = new Request([
            'q'       => 'some/path/from/drupal',
            'foo'     => 'c|d|e',
            'test'    => 'test',
            'bar'     => 'baz',
            '_st'     => 'toto',
            '_by'     => 'asc',
            '_limit'  => 12,
            '_page'   => 3,
        ], [], ['_route' => 'some/path']);

        $factory = new QueryFactory();

        $inputDefinition = new InputDefinition([
            'filter_list' => ['foo', 'test', 'bar', 'baz', 'some'],
            'limit_allowed' => false,
            'limit_param'   => '_limit',
        ]);

        $query = $factory->fromRequest($inputDefinition, $request);
        // Limit is not overridable per default
        self::assertSame(Query::LIMIT_DEFAULT, $query->getLimit());
        // Parameters are not changed
        self::assertFalse($query->hasSortField());
        self::assertSame($inputDefinition->getDefaultSortField(), $query->getSortField());
        self::assertSame(Query::SORT_DESC, $query->getSortOrder());
        self::assertSame(1, $query->getPageNumber());
        self::assertSame(0, $query->getOffset());

        $inputDefinition = new InputDefinition([
            'filter_list'       => ['foo', 'test', 'bar', 'baz', 'some'],
            'limit_allowed'     => true,
            'limit_param'       => '_limit',
            'pager_enable'      => true,
            'pager_param'       => '_page',
            'sort_allowed_list' => ['toto'],
            'sort_field_param'  => '_st',
            'sort_order_param'  => '_by'
        ]);
        $query = $factory->fromRequest($inputDefinition, $request);
        // Limit is not overridable per default
        self::assertSame(12, $query->getLimit());
        self::assertTrue($query->hasSortField());
        self::assertSame('toto', $query->getSortField());
        self::assertSame(Query::SORT_ASC, $query->getSortOrder());
        // Pagination
        self::assertSame(3, $query->getPageNumber());
        self::assertSame(24, $query->getOffset());

        // Route, get, set
        self::assertSame('some/path', $query->getRoute());
        self::assertTrue($query->has('foo'));
        self::assertFalse($query->has('non_existing'));
        self::assertSame(['c', 'd', 'e'], $query->get('foo', 'oula'));
        self::assertSame(27, $query->get('non_existing', 27));
    }

    /**
     * Tests behaviour with search
     */
    public function testWithSearch(): void
    {
        $search = 'foo:a foo:d foo:f some:other fulltext search';

        $request = new Request([
            'q'       => 'some/path',
            'foo'     => 'c|d|e',
            'test'    => 'test',
            'bar'     => 'baz',
            'search'  => $search,
        ]);

        $inputDefinition = new InputDefinition([
            'filter_list' => ['foo', 'test', 'bar', 'baz', 'some'],
            'search_enable' => true,
            'search_param'  => 'search',
            'search_parse'  => true,
        ]);

        $factory = new QueryFactory();
        $queryFromArray = $factory->fromArray($inputDefinition, ['foo' => ['c', 'd', 'e'], 'bar' => 'baz', 'search' => $search]);
        $queryFromRequest = $factory->fromRequest($inputDefinition, $request);

        foreach ([$queryFromArray, $queryFromRequest] as $query) {
            self::assertInstanceOf(Query::class, $query);

            // Test the "all" query
            $all = $query->all();
            self::assertArrayNotHasKey('q', $all);
            self::assertArrayHasKey('foo', $all);
            self::assertArrayHasKey('some', $all);
            // Both are merged, no duplicates, outside of base query is dropped
            self::assertCount(5, $all['foo']);
            self::assertContains('a', $all['foo']);
            self::assertContains('c', $all['foo']);
            self::assertContains('d', $all['foo']);
            self::assertContains('e', $all['foo']);
            self::assertContains('f', $all['foo']);
            // Search only driven query is there, and flattened since there's only one element
            self::assertSame('other', $all['some']);

            // Test the "route parameters" query
            $params = $query->getRouteParameters();
            self::assertArrayNotHasKey('q', $params);
            self::assertArrayHasKey('foo', $params);
            self::assertArrayNotHasKey('some', $params);
            // Route parameters are left untouched, even if it matches some base query
            // parameters, only change that may be done in that is input cleaning and
            // array expansion or flattening of values
            self::assertTrue(\is_string($params['foo']));
            $fooValues = \explode(Query::URL_VALUE_SEP, $params['foo']);
            self::assertCount(3, $fooValues);
            self::assertContains('c', $fooValues);
            self::assertContains('d', $fooValues);
            self::assertContains('e', $fooValues);
            // Search is flattened
            self::assertSame($search, $params['search']);
        }
    }

    /**
     * Tests behaviour with search
     */
    public function testWithBaseQuery(): void
    {
        $request = new Request([
            'q'       => 'some/path',
            'foo'     => 'b|c|d|e',
            'test'    => 'test',
            'bar'     => 'baz',
        ]);

        $baseQuery = ['foo' => ['a', 'b', 'c']];

        $inputDefinition = new InputDefinition([
            'filter_list' => ['foo', 'test', 'bar', 'baz', 'some'],
            'base_query'    => $baseQuery,
            'search_enable' => true,
            'search_param'  => 'search',
            'search_parse'  => true,
        ]);

        $factory = new QueryFactory();
        // Items in the query parameter are in an another order than the base
        // query filter: this tests that items, when ordered in a different
        // order, will still be removed from the router parameters if they
        // match
        $queryFromArray = $factory->fromArray($inputDefinition, ['foo' => ['d', 'c', 'b', 'e'], 'bar' => 'baz']);
        $queryFromRequest = $factory->fromRequest($inputDefinition, $request);

        foreach ([$queryFromArray, $queryFromRequest] as $query) {
            self::assertInstanceOf(Query::class, $query);

            // Test the "all" query
            $all = $query->all();
            // Only those from base query are allowed, and those which are
            // not explicitely added to parameter are removed
            // i.e. base query is [a, b] and current query is [b, c] then
            // only b is visible (asked by query), a is dropped (not in query)
            // and c is dropped (not ine base query)
            self::assertCount(2, $all['foo']);
            self::assertNotContains('a', $all['foo']);
            self::assertContains('b', $all['foo']);
            self::assertContains('c', $all['foo']);
            self::assertNotContains('d', $all['foo']);
            self::assertNotContains('e', $all['foo']);

            // Test the "route parameters" query
            $params = $query->getRouteParameters();
            self::assertArrayNotHasKey('q', $params);
            self::assertArrayHasKey('foo', $params);
            self::assertArrayNotHasKey('some', $params);
            // Route parameters are subject to base query change too
            self::assertTrue(\is_string($params['foo']));
            $fooValues = \explode(Query::URL_VALUE_SEP, $params['foo']);
            self::assertCount(2, $fooValues);
            self::assertContains('b', $fooValues);
            self::assertContains('c', $fooValues);
            self::assertNotContains('d', $fooValues);
            self::assertNotContains('e', $fooValues);

            self::assertSame($baseQuery, $inputDefinition->getBaseQuery());
        }
    }

    /**
     * Tests behaviour without search
     */
    public function testWithoutSearch(): void
    {
        $search = 'foo:a foo:d foo:f some:other fulltext search';

        $request = new Request([
            'q'       => 'some/path',
            'foo'     => 'c|d|e',
            'test'    => 'test',
            'bar'     => 'baz',
            'search'  => $search,
        ]);

        $inputDefinition = new InputDefinition([
            'filter_list' => ['foo', 'test', 'bar', 'baz', 'some'],
            'search_enable' => true,
            'search_field'  => ['foo'],
            'search_param'  => 'search',
            'search_parse'  => false,
        ]);

        $factory = new QueryFactory();
        $queryFromArray = $factory->fromArray($inputDefinition, ['foo' => ['c', 'd', 'e'], 'bar' => 'baz', 'search' => $search]);
        $queryFromRequest = $factory->fromRequest($inputDefinition, $request);

        foreach ([$queryFromArray, $queryFromRequest] as $query) {
            self::assertInstanceOf(Query::class, $query);

            // Test the "all" query
            $all = $query->all();
            self::assertArrayNotHasKey('q', $all);
            self::assertArrayNotHasKey('some', $all);
            self::assertArrayNotHasKey('other', $all);
            self::assertArrayHasKey('foo', $all);
            // Both are merged, no duplicates, outside of base query is dropped
            self::assertCount(3, $all['foo']);
            self::assertNotContains('a', $all['foo']);
            self::assertNotContains('f', $all['foo']);
            self::assertContains('c', $all['foo']);
            self::assertContains('d', $all['foo']);
            self::assertContains('e', $all['foo']);
            // 'f' is only visible in parsed search, drop it
            self::assertNotContains('f', $all['foo']);
            // Search is not a filter, thus is is not in there
            self::assertNotContains('search', $all['foo']);

            // Test the "route parameters" query
            $params = $query->getRouteParameters();
            self::assertArrayNotHasKey('q', $params);
            self::assertArrayHasKey('foo', $params);
            self::assertArrayNotHasKey('some', $params);
            // Route parameters are left untouched, even if it matches some base query
            // parameters, only change that may be done in that is input cleaning and
            // array expansion or flattening of values
            self::assertTrue(\is_string($params['foo']));
            $fooValues = \explode(Query::URL_VALUE_SEP, $params['foo']);
            self::assertCount(3, $fooValues);
            self::assertContains('c', $fooValues);
            self::assertContains('d', $fooValues);
            self::assertContains('e', $fooValues);
            // Search is flattened
            self::assertSame($search, $params['search']);
            self::assertSame($search, $params['search']);
        }
    }

    /**
     * Tests query string parser
     */
    public function testQueryStringParser(): void
    {
        $queryString = 'field1:13 foo:"bar baz" bar:2 innner:"this one has:inside" full text bar:test bar:bar not:""';

        $parsed = (new QueryStringParser())->parse($queryString, 's');

        self::assertCount(1, $parsed['field1']);
        self::assertSame('13', $parsed['field1'][0]);

        self::assertCount(1, $parsed['foo']);
        self::assertSame('bar baz', $parsed['foo'][0]);

        self::assertCount(3, $parsed['bar']);
        self::assertSame('2', $parsed['bar'][0]);
        self::assertSame('test', $parsed['bar'][1]);
        self::assertSame('bar', $parsed['bar'][2]);

        self::assertArrayNotHasKey('has', $parsed);
        self::assertCount(1, $parsed['innner']);
        self::assertSame('this one has:inside', $parsed['innner'][0]);

        self::assertArrayNotHasKey('not', $parsed);

        self::assertCount(2, $parsed['s']);
        self::assertSame('full', $parsed['s'][0]);
        self::assertSame('text', $parsed['s'][1]);
    }
}
