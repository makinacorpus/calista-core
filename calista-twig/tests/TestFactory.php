<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\Tests;

use MakinaCorpus\Calista\Twig\Extension\BlockExtension;
use MakinaCorpus\Calista\Twig\Extension\PageExtension;
use MakinaCorpus\Calista\Twig\View\DefaultTwigBlockRenderer;
use MakinaCorpus\Calista\View\ViewManager;
use MakinaCorpus\Calista\View\Tests\PropertyRendererTest;
use MakinaCorpus\Calista\View\ViewRendererRegistry\ArrayViewRendererRegistry;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TestFactory
{
    public static function createTestTemplatesLoaderDefinition(): array
    {
        return [
            '@calista/page/filter-bootstrap3.html.twig' => dirname(__DIR__) . '/templates/page/filter-bootstrap3.html.twig',
            '@calista/page/filter-bootstrap4.html.twig' => dirname(__DIR__) . '/templates/page/filter-bootstrap4.html.twig',
            '@calista/page/filter.html.twig' => dirname(__DIR__) . '/templates/page/filter.html.twig',
            '@calista/page/page-bootstrap3.html.twig' => dirname(__DIR__) . '/templates/page/page-bootstrap3.html.twig',
            '@calista/page/page-bootstrap4.html.twig' => dirname(__DIR__) . '/templates/page/page-bootstrap4.html.twig',
            '@calista/page/page.html.twig' => dirname(__DIR__) . '/templates/page/page.html.twig',
            '@calista/test/functional/custom.html.twig' => __DIR__ . '/templates/functional/custom.html.twig',
            '@calista/test/functional/first.html.twig' => __DIR__ . '/templates/functional/first.html.twig',
            '@calista/test/functional/second.html.twig' => __DIR__ . '/templates/functional/second.html.twig',
            '@calista/test/unit/custom.html.twig' => __DIR__ . '/templates/unit/custom.html.twig',
            '@calista/test/unit/first-extend.html.twig' => __DIR__ . '/templates/unit/first-extend.html.twig',
            '@calista/test/unit/first.html.twig' => __DIR__ . '/templates/unit/first.html.twig',
            '@calista/test/unit/second.html.twig' => __DIR__ . '/templates/unit/second.html.twig',
        ];
    }

    /**
     * Create a twig environment with the bare minimum we need
     */
    static public function createTwigEnv(): Environment
    {
        $twigEnv = new Environment(
            new TestTwigLoader(self::createTestTemplatesLoaderDefinition()),
            [
                'debug' => true,
                'strict_variables' => true,
                'autoescape' => 'html',
                'cache' => false,
                'auto_reload' => null,
                'optimizations' => -1,
            ]
        );

        $twigEnv->addFunction(new TwigFunction('path', function ($route, $routeParameters = []) {
            return $route . '&' . \http_build_query($routeParameters);
        }), ['is_safe' => ['html']]);
        $twigEnv->addFilter(new TwigFilter('trans', function ($string, $params = []) {
            return \strtr($string, $params);
        }));
        $twigEnv->addFilter(new TwigFilter('t', function ($string, $params = []) {
            return \strtr($string, $params);
        }));
        $twigEnv->addFilter(new TwigFilter('time_diff', function ($value) {
            return (string)$value;
        }));

        $twigEnv->addExtension(
            new PageExtension(
                new RequestStack(),
                PropertyRendererTest::createPropertyRenderer(),
                new ViewManager(
                    new ArrayViewRendererRegistry([]),
                    new EventDispatcher(),
                )
            )
        );

        return $twigEnv;
    }

    public static function createDefaultTwigBlockRenderer(Environment $twigEnv, ?array $templates = null): DefaultTwigBlockRenderer
    {
        $blockRenderer = new DefaultTwigBlockRenderer($twigEnv, $templates ?? [
            '@calista/page/page.html.twig',
            '@calista/page/filter.html.twig',
        ]);

        $twigEnv->addExtension(new BlockExtension($blockRenderer));

        return $blockRenderer;
    }
}
