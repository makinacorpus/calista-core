<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\Tests;

use MakinaCorpus\Calista\Twig\View\CustomTwigBlockRenderer;
use PHPUnit\Framework\TestCase;

final class TwigBlockRendererUnitTest extends TestCase
{
    public function testFallbackOnFirst(): void
    {
        $blockRenderer = TestFactory::createDefaultTwigBlockRenderer(
            TestFactory::createTwigEnv(),
            [
                '@calista/test/unit/second.html.twig',
                '@calista/test/unit/first.html.twig',
            ]
        );

        self::assertSame(
            'page from first',
            \trim($blockRenderer->render())
        );
    }
    public function testArgumentPropagation(): void
    {
        $blockRenderer = TestFactory::createDefaultTwigBlockRenderer(
            TestFactory::createTwigEnv(),
            [
                '@calista/test/unit/second.html.twig',
                '@calista/test/unit/first.html.twig',
            ]
        );

        self::assertSame(
            'page from first this is an argument',
            \trim($blockRenderer->render(['foo' => 'this is an argument']))
        );
    }

    public function testOverrideInSecond(): void
    {
        $blockRenderer = TestFactory::createDefaultTwigBlockRenderer(
            TestFactory::createTwigEnv(),
            [
                '@calista/test/unit/second.html.twig',
                '@calista/test/unit/first.html.twig',
            ]
        );

        self::assertSame(
            'override in second in second',
            \trim($blockRenderer->renderBlock('override_in_second'))
        );
    }

    public function testFirstExtendedOverrideFirst(): void
    {
        $blockRenderer = TestFactory::createDefaultTwigBlockRenderer(
            TestFactory::createTwigEnv(),
            [
                '@calista/test/unit/first-extend.html.twig',
                '@calista/test/unit/second.html.twig',
                '@calista/test/unit/first.html.twig',
            ]
        );

        self::assertSame(
            'override in second in first extended',
            \trim($blockRenderer->renderBlock('override_in_second'))
        );
    }

    public function testOverrideInCustom(): void
    {
        $blockRenderer = TestFactory::createDefaultTwigBlockRenderer(
            TestFactory::createTwigEnv(),
            [
                '@calista/test/unit/second.html.twig',
                '@calista/test/unit/first.html.twig',
            ]
        );

        $customBlockRenderer = new CustomTwigBlockRenderer(
            $blockRenderer,
            '@calista/test/unit/custom.html.twig'
        );

        self::assertSame(
            'page from custom this is an argument',
            \trim($customBlockRenderer->render(['foo' => 'this is an argument']))
        );
    }

    public function testNonExistingBlockRaiseError(): void
    {
        $blockRenderer = TestFactory::createDefaultTwigBlockRenderer(
            TestFactory::createTwigEnv(),
            [
                '@calista/test/unit/second.html.twig',
                '@calista/test/unit/first.html.twig',
            ]
        );

        self::expectExceptionMessageMatches('/could not be found in template/');
        $blockRenderer->renderBlock('bar', []);
    }
}
