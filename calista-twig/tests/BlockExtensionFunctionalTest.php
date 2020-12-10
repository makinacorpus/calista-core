<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\Tests;

use PHPUnit\Framework\TestCase;

final class BlockExtensionFunctionalTest extends TestCase
{
    public function testRenderBlockInFirstRenderSecond(): void
    {
        $blockRenderer = TestFactory::createDefaultTwigBlockRenderer(
            TestFactory::createTwigEnv(),
            [
                '@calista/test/functional/second.html.twig',
                '@calista/test/functional/first.html.twig',
            ]
        );

        self::assertSame(
            'page from first arbitrary component 1 in second',
            \preg_replace('/[\s\n]+/ims', ' ', \trim($blockRenderer->render()))
        );
    }

    public function testRenderBlockInFirstRenderCustom(): void
    {
        self::markTestSkipped("Not implemented yet.");
    }
}
