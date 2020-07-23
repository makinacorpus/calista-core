<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource\Tests;

use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use PHPUnit\Framework\TestCase;

/**
 * Test basic datasource functionnality and API
 */
final class DatasourceTest extends TestCase
{
    /**
     * Tests basics
     */
    public function testDefaultResultIterator(): void
    {
        $result = new DefaultDatasourceResult();
        self::assertInstanceOf(\ArrayIterator::class, $result->getIterator());
        self::assertCount(0, $result);
        self::assertCount(0, $result->getIterator());

        $result = new DefaultDatasourceResult(new \EmptyIterator());
        self::assertInstanceOf(\EmptyIterator::class, $result->getIterator());
        self::assertSame(0, $result->count());
        // We cannot count() an \EmptyIterator instance, so this isn't tested here

        $result = new DefaultDatasourceResult([1, 2, 3]);
        self::assertInstanceOf(\ArrayIterator::class, $result->getIterator());
        self::assertCount(3, $result);
        self::assertCount(3, $result->getIterator());

        // Test that result iterator count is not modified by multiple runs
        $result = new DefaultDatasourceResult(['a', 'b', 'c', 'd', 'e']);
        self::assertSame(5, $result->count());
        self::assertSame(5, $result->count());

        // Test page methods basics
        $result->setPagerInformation(1, 17);
        self::assertSame(17, $result->getTotalCount());
    }
}
