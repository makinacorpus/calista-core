<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests;

use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\Tests\Mock\DummyViewRenderer;
use PHPUnit\Framework\TestCase;

final class AbstractViewRendererTest extends TestCase
{
    public function testRenderInStream(): void
    {
        $renderer = new DummyViewRenderer();
        $resource = \fopen('php://memory', 'w+');

        $renderer->renderInStream(View::empty(), $resource);
        \rewind($resource);
        self::assertSame('Dummy content.', \stream_get_contents($resource));
    }

    public function testRenderInStreamRaiseErrorIfNotResource(): void
    {
        $renderer = new DummyViewRenderer();
        $resource = 'BLA';

        self::expectException(\InvalidArgumentException::class);
        $renderer->renderInStream(View::empty(), $resource);
    }

    public function testRenderInFile(): void
    {
        $filename = \tempnam(\sys_get_temp_dir(), 'calista-view-file-');

        $renderer = new DummyViewRenderer();
        $renderer->renderInFile(View::empty(), $filename);

        self::assertSame('Dummy content.', \file_get_contents($filename));
    }

    public function testRenderInExistingButEmptyFile(): void
    {
        $filename = \tempnam(\sys_get_temp_dir(), 'calista-view-file-');
        \file_put_contents($filename, '');
        self::assertFileExists($filename);

        $renderer = new DummyViewRenderer();
        $renderer->renderInFile(View::empty(), $filename);

        self::assertSame('Dummy content.', \file_get_contents($filename));
    }

    public function testRenderInFileRaiseErrorIfFileExistsAndSizeIsMoreThanZero(): void
    {
        $filename = \tempnam(\sys_get_temp_dir(), 'calista-view-file-');
        \file_put_contents($filename, 'Foo');
        self::assertFileExists($filename);

        $renderer = new DummyViewRenderer();

        self::expectException(\InvalidArgumentException::class);
        $renderer->renderInFile(View::empty(), $filename);
    }
}
