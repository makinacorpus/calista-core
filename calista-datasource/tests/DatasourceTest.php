<?php

namespace MakinaCorpus\Calista\Datasource\Tests;

use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use PHPUnit\Framework\TestCase;

/**
 * Test basic datasource functionnality and API
 */
class DatasourceTest extends TestCase
{
    /**
     * Tests basics
     */
    public function testDefaultResultIterator()
    {
        $result = new DefaultDatasourceResult();
        $this->assertInstanceOf(\ArrayIterator::class, $result->getIterator());
        $this->assertCount(0, $result);
        $this->assertCount(0, $result->getIterator());
        $this->assertFalse($result->canStream());

        $result = new DefaultDatasourceResult('', new \EmptyIterator());
        $this->assertInstanceOf(\EmptyIterator::class, $result->getIterator());
        $this->assertSame(0, $result->count());
        // We cannot count() an \EmptyIterator instance, so this isn't tested here
        $this->assertTrue($result->canStream());

        $result = new DefaultDatasourceResult('', [1, 2, 3]);
        $this->assertInstanceOf(\ArrayIterator::class, $result->getIterator());
        $this->assertCount(3, $result);
        $this->assertCount(3, $result->getIterator());
        $this->assertFalse($result->canStream());

        // Test that result iterator count is not modified by multiple runs
        $result = new DefaultDatasourceResult('', ['a', 'b', 'c', 'd', 'e']);
        $this->assertSame(5, $result->count());
        $this->assertSame(5, $result->count());

        // Test page methods basics
        $this->assertFalse($result->hasTotalItemCount());
        $result->setTotalItemCount(17);
        $this->assertTrue($result->hasTotalItemCount());
        $this->assertSame(17, $result->getTotalCount());
    }
}
