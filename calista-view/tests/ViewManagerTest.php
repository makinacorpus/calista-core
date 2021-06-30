<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests;

use PHPUnit\Framework\TestCase;
use MakinaCorpus\Calista\View\ViewManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use MakinaCorpus\Calista\View\ViewRendererRegistry\ArrayViewRendererRegistry;
use MakinaCorpus\Calista\View\Tests\Mock\CustomViewBuilderNoParameters;

final class ViewManagerTest extends TestCase 
{
    public function testUsesCustomBuilder(): void
    {
        $viewManager = new ViewManager(
            new ArrayViewRendererRegistry([]),
            new EventDispatcher(),
        );

        self::expectExceptionMessage("I shall not be called because I am a mock");
        $viewManager->createViewBuilder(CustomViewBuilderNoParameters::class);
    }
}
