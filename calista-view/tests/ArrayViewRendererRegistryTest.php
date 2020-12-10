<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests;

use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\Stream\CsvStreamViewRenderer;
use MakinaCorpus\Calista\View\ViewRendererRegistry\ArrayViewRendererRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class ArrayViewRendererRegistryTest extends TestCase 
{
    public function testGetOneThatExist(): void
    {
        $expected = new CsvStreamViewRenderer(new PropertyRenderer(new PropertyAccessor()));
        $registry = new ArrayViewRendererRegistry([
            'foo' => $expected,
        ]);

        $actual = $registry->getViewRenderer('foo');
        self::assertSame($expected, $actual);
    }

    public function testGetOneThatDoesNotExistRaiseError(): void
    {
        $registry = new ArrayViewRendererRegistry([
            'foo' => new CsvStreamViewRenderer(new PropertyRenderer(new PropertyAccessor())),
        ]);

        self::expectException(\InvalidArgumentException::class);
        $registry->getViewRenderer('bar');
    }
}
