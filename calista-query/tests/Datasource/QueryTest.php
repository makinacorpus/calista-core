<?php

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
class QueryTest extends TestCase
{
    /**
     * Tests basics
     */
    public function testSortHandling()
    {
        $request = new Request(['st' => 'b', 'by' => 'asc', 'foo' => 'barr'], [], ['_route' => 'my_route']);
        $inputDefinition = new InputDefinition(['sort_allowed_list' => ['a', 'b', 'c']]);
        $query = (new QueryFactory())->fromRequest($inputDefinition, $request);

        $this->assertSame(3, count($inputDefinition->getAllowedSorts()));
        $this->assertSame(Query::SORT_DESC, $inputDefinition->getDefaultSortOrder());
        $this->assertSame('a', $inputDefinition->getDefaultSortField());
        $this->assertSame(Query::SORT_ASC, $query->getSortOrder());
        $this->assertSame('b', $query->getSortField());
    }

    /**
     * Tests basic accesors
     */
    public function testQueryBasics()
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
        $this->assertSame(Query::LIMIT_DEFAULT, $query->getLimit());
        // Parameters are not changed
        $this->assertFalse($query->hasSortField());
        $this->assertSame($inputDefinition->getDefaultSortField(), $query->getSortField());
        $this->assertSame(Query::SORT_DESC, $query->getSortOrder());
        $this->assertSame(1, $query->getPageNumber());
        $this->assertSame(0, $query->getOffset());

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
        $this->assertSame(12, $query->getLimit());
        $this->assertTrue($query->hasSortField());
        $this->assertSame('toto', $query->getSortField());
        $this->assertSame(Query::SORT_ASC, $query->getSortOrder());
        // Pagination
        $this->assertSame(3, $query->getPageNumber());
        $this->assertSame(24, $query->getOffset());

        // Route, get, set
        $this->assertSame('some/path', $query->getRoute());
        $this->assertTrue($query->has('foo'));
        $this->assertFalse($query->has('non_existing'));
        $this->assertSame(['c', 'd', 'e'], $query->get('foo', 'oula'));
        $this->assertSame(27, $query->get('non_existing', 27));
    }

    /**
     * Tests behaviour with search
     */
    public function testWithSearch()
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
            $this->assertInstanceOf(Query::class, $query);

            // Test the "all" query
            $all = $query->all();
            $this->assertArrayNotHasKey('q', $all);
            $this->assertArrayHasKey('foo', $all);
            $this->assertArrayHasKey('some', $all);
            // Both are merged, no duplicates, outside of base query is dropped
            $this->assertCount(5, $all['foo']);
            $this->assertContains('a', $all['foo']);
            $this->assertContains('c', $all['foo']);
            $this->assertContains('d', $all['foo']);
            $this->assertContains('e', $all['foo']);
            $this->assertContains('f', $all['foo']);
            // Search only driven query is there, and flattened since there's only one element
            $this->assertSame('other', $all['some']);

            // Test the "route parameters" query
            $params = $query->getRouteParameters();
            $this->assertArrayNotHasKey('q', $params);
            $this->assertArrayHasKey('foo', $params);
            $this->assertArrayNotHasKey('some', $params);
            // Route parameters are left untouched, even if it matches some base query
            // parameters, only change that may be done in that is input cleaning and
            // array expansion or flattening of values
            $this->assertTrue(is_string($params['foo']));
            $fooValues = explode(Query::URL_VALUE_SEP, $params['foo']);
            $this->assertCount(3, $fooValues);
            $this->assertContains('c', $fooValues);
            $this->assertContains('d', $fooValues);
            $this->assertContains('e', $fooValues);
            // Search is flattened
            $this->assertSame($search, $params['search']);
        }
    }

    /**
     * Tests behaviour with search
     */
    public function testWithBaseQuery()
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
            $this->assertInstanceOf(Query::class, $query);

            // Test the "all" query
            $all = $query->all();
            // Only those from base query are allowed, and those which are
            // not explicitely added to parameter are removed
            // i.e. base query is [a, b] and current query is [b, c] then
            // only b is visible (asked by query), a is dropped (not in query)
            // and c is dropped (not ine base query)
            $this->assertCount(2, $all['foo']);
            $this->assertNotContains('a', $all['foo']);
            $this->assertContains('b', $all['foo']);
            $this->assertContains('c', $all['foo']);
            $this->assertNotContains('d', $all['foo']);
            $this->assertNotContains('e', $all['foo']);

            // Test the "route parameters" query
            $params = $query->getRouteParameters();
            $this->assertArrayNotHasKey('q', $params);
            $this->assertArrayHasKey('foo', $params);
            $this->assertArrayNotHasKey('some', $params);
            // Route parameters are subject to base query change too
            $this->assertTrue(is_string($params['foo']));
            $fooValues = explode(Query::URL_VALUE_SEP, $params['foo']);
            $this->assertCount(2, $fooValues);
            $this->assertContains('b', $fooValues);
            $this->assertContains('c', $fooValues);
            $this->assertNotContains('d', $fooValues);
            $this->assertNotContains('e', $fooValues);

            $this->assertSame($baseQuery, $inputDefinition->getBaseQuery());
        }
    }

    /**
     * Tests behaviour without search
     */
    public function testWithoutSearch()
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
            $this->assertInstanceOf(Query::class, $query);

            // Test the "all" query
            $all = $query->all();
            $this->assertArrayNotHasKey('q', $all);
            $this->assertArrayNotHasKey('some', $all);
            $this->assertArrayNotHasKey('other', $all);
            $this->assertArrayHasKey('foo', $all);
            // Both are merged, no duplicates, outside of base query is dropped
            $this->assertCount(3, $all['foo']);
            $this->assertNotContains('a', $all['foo']);
            $this->assertNotContains('f', $all['foo']);
            $this->assertContains('c', $all['foo']);
            $this->assertContains('d', $all['foo']);
            $this->assertContains('e', $all['foo']);
            // 'f' is only visible in parsed search, drop it
            $this->assertNotContains('f', $all['foo']);
            // Search is not a filter, thus is is not in there
            $this->assertNotContains('search', $all['foo']);

            // Test the "route parameters" query
            $params = $query->getRouteParameters();
            $this->assertArrayNotHasKey('q', $params);
            $this->assertArrayHasKey('foo', $params);
            $this->assertArrayNotHasKey('some', $params);
            // Route parameters are left untouched, even if it matches some base query
            // parameters, only change that may be done in that is input cleaning and
            // array expansion or flattening of values
            $this->assertTrue(is_string($params['foo']));
            $fooValues = explode(Query::URL_VALUE_SEP, $params['foo']);
            $this->assertCount(3, $fooValues);
            $this->assertContains('c', $fooValues);
            $this->assertContains('d', $fooValues);
            $this->assertContains('e', $fooValues);
            // Search is flattened
            $this->assertSame($search, $params['search']);
            $this->assertSame($search, $params['search']);
        }
    }

    /**
     * Tests query string parser
     */
    public function testQueryStringParser()
    {
        $queryString = 'field1:13 foo:"bar baz" bar:2 innner:"this one has:inside" full text bar:test bar:bar not:""';

        $parsed = (new QueryStringParser())->parse($queryString, 's');

        $this->assertCount(1, $parsed['field1']);
        $this->assertSame('13', $parsed['field1'][0]);

        $this->assertCount(1, $parsed['foo']);
        $this->assertSame('bar baz', $parsed['foo'][0]);

        $this->assertCount(3, $parsed['bar']);
        $this->assertSame('2', $parsed['bar'][0]);
        $this->assertSame('test', $parsed['bar'][1]);
        $this->assertSame('bar', $parsed['bar'][2]);

        $this->assertArrayNotHasKey('has', $parsed);
        $this->assertCount(1, $parsed['innner']);
        $this->assertSame('this one has:inside', $parsed['innner'][0]);

        $this->assertArrayNotHasKey('not', $parsed);

        $this->assertCount(2, $parsed['s']);
        $this->assertSame('full', $parsed['s'][0]);
        $this->assertSame('text', $parsed['s'][1]);
    }
}
